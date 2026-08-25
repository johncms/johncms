<?php

/**
 * This file is part of JohnCMS Content Management System.
 *
 * @copyright JohnCMS Community
 * @license   https://opensource.org/licenses/GPL-3.0 GPL-3.0
 * @link      https://johncms.com JohnCMS Project
 */

declare(strict_types=1);

use Johncms\Auth\Authorization\RoleSeeder;
use Johncms\Auth\Password\PasswordHasherInterface;
use Johncms\Auth\Session\AuthCookieFactory;
use Johncms\Auth\Session\AuthSessionManager;
use Johncms\Console\Commands\CacheClearCommand;
use Johncms\Modules\Admin\Application\UseCases\RebuildSmiliesCacheUseCase;
use Johncms\Modules\Admin\Domain\Services\LanguageFilesManagerInterface;
use Johncms\Http\Environment;
use Johncms\Console\Commands\ModuleSyncCommand;
use Johncms\Modules\Installer;
use Johncms\Modules\ModuleRegistryFactory;
use Johncms\Http\Request;
use Johncms\Users\User;
use Johncms\Validator\Rules\EmailAddress;
use Johncms\Validator\Rules\NotEmpty;
use Johncms\Validator\ValidatorInterface;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\NullOutput;

/** @var Request $request Built by the installer entry point, which includes this file. */

di(PDO::class);

$viewData += ['title' => __('Setting'), 'page_title' => __('Setting')];

$fields = [
    // getSchemeAndHttpHost() replaces the former isHttps() + SERVER_NAME pair and additionally
    // keeps a non-standard port. The sanitize filter on the submitted value is preserved.
    'homeurl'        => $request->request->filter(
        'homeurl',
        $request->getSchemeAndHttpHost(),
        FILTER_SANITIZE_URL
    ),
    'email'          => $request->body('email'),
    'admin_login'    => $request->body('admin_login', 'admin'),
    'admin_password' => $request->body('admin_password'),
    'install_demo'   => $request->bodyInt('install_demo'),
];

$errors = [];

if ($request->getMethod() === 'POST') {
    $rules = [
        'homeurl'        => [new NotEmpty()],
        // Required, and its host has to resolve: the site cannot mail its administrator at an
        // address that does not exist.
        'email'          => [new EmailAddress(checkMxRecord: true)],
        'admin_login'    => [new NotEmpty()],
        'admin_password' => [new NotEmpty()],
    ];

    $result = di(ValidatorInterface::class)->validate($fields, $rules);
    if ($result->isValid()) {
        // Получаем конфиг по умолчанию
        $config = config('johncms');

        // Изменяем некоторые параметры
        $config['homeurl'] = $fields['homeurl'];
        $config['email'] = $fields['email'];
        $config['lng'] = $translator->getLocale();
        $config['lng_list'] = di(LanguageFilesManagerInterface::class)->getInstalled();

        $system_settings = [
            'johncms' => $config,
        ];
        $configFile = "<?php\n\n" . 'return ' . var_export($system_settings, true) . ";\n";

        if (file_put_contents(CONFIG_PATH . 'autoload/system.local.php', $configFile)) {
            // The built-in roles, from the same seeder the upgrade command uses, so a fresh
            // installation and an upgraded one cannot end up with different sets.
            di(RoleSeeder::class)->seed();

            // Регистрируем пользователя
            $hasher = di(PasswordHasherInterface::class);
            $user = new User();
            $user->fill(
                [
                    'name'            => $fields['admin_login'],
                    'name_lat'        => mb_strtolower($fields['admin_login']),
                    'mail'            => $fields['email'],
                    'www'             => $fields['homeurl'],
                    'datereg'         => time(),
                    'lastdate'        => time(),
                    'rights'          => 9,
                    // The `ip` attribute is cast via Johncms\Casts\Ip, which expects a string IP and converts it itself.
                    'ip'              => $_SERVER['REMOTE_ADDR'],
                    'browser'         => htmlentities($_SERVER['HTTP_USER_AGENT']),
                    'preg'            => 1,
                    'email_confirmed' => 1,
                    'sex'             => 'm',
                    'about'           => '',
                    'set_user'        => [],
                    'set_forum'       => [],
                    'set_mail'        => [],
                    'smileys'         => [],
                ]
            );
            $user->password = $hasher->hash($fields['admin_password']);
            $user->save();

            // Signs the new administrator in. The installer answers outside the kernel, so the
            // cookie is emitted directly instead of being queued — its attributes still come from
            // AuthCookieFactory, so they cannot drift from the ones the site sets later.
            $issued = di(AuthSessionManager::class)->start(
                $user->id,
                true,
                di(Environment::class)->getClientInfo()
            );
            header(
                'Set-Cookie: ' . di(AuthCookieFactory::class)->create(
                    $issued->token,
                    true,
                    $issued->session->expires_at,
                    $request->isSecure()
                ),
                false
            );

            if (! empty($fields['install_demo'])) {
                // Seed a couple of regular users referenced by module demo data (authors, commenters).
                $demoUsers = [
                    ['name' => 'Alex', 'sex' => 'm', 'mail' => 'alex@example.com', 'ip' => '192.0.2.10'],
                    ['name' => 'Maria', 'sex' => 'f', 'mail' => 'maria@example.com', 'ip' => '192.0.2.20'],
                ];
                foreach ($demoUsers as $demoUser) {
                    $demoModel = new User();
                    $demoModel->fill(
                        [
                            'name'            => $demoUser['name'],
                            'name_lat'        => mb_strtolower($demoUser['name']),
                            'mail'            => $demoUser['mail'],
                            'www'             => '',
                            'datereg'         => time(),
                            'lastdate'        => time(),
                            'rights'          => 0,
                            // The `ip` attribute is cast via Johncms\Casts\Ip, which expects a string IP and converts it itself.
                            'ip'              => $demoUser['ip'],
                            'browser'         => htmlentities($_SERVER['HTTP_USER_AGENT']),
                            'preg'            => 1,
                            'email_confirmed' => 1,
                            'sex'             => $demoUser['sex'],
                            'about'           => '',
                            'set_user'        => [],
                            'set_forum'       => [],
                            'set_mail'        => [],
                            'smileys'         => [],
                        ]
                    );
                    $demoModel->password = $hasher->hash('demo');
                    $demoModel->save();
                }

                foreach (ModuleRegistryFactory::registry()->enabled() as $manifest) {
                    $installerClass = 'Johncms\\Modules\\' . ucfirst(basename($manifest->key)) . '\\Install\\Installer';

                    if (is_a($installerClass, Installer::class, true)) {
                        (new $installerClass($manifest->alias))->installDemoData();
                    }
                }
            }

            // Write down what this installation put in. Without it the site still runs — a module
            // of the release counts as installed until the state file says otherwise — but the
            // record every later install, update and removal is written against would be missing.
            di(ModuleSyncCommand::class)->run(new ArrayInput([]), new NullOutput());

            // Drop cached counters so freshly seeded data is reflected right away.
            (new CacheClearCommand())->run(new ArrayInput([]), new NullOutput());

            // Build the smilies cache so text formatters have a valid list from the first request.
            try {
                di(RebuildSmiliesCacheUseCase::class)->execute();
            } catch (Throwable) {
                // Smilies cache is non-critical for install; it can be rebuilt later from the admin panel.
            }

            header('Location: /install/?step=5');
            exit;
        }

        $errors['unknown'][] = __("ERROR: Can't write system.local.php");
    } else {
        $errors = $result->getErrors();
    }
}

$data = [
    'errors'             => $errors,
    'fields'             => $fields,
    'next_step_disabled' => false,
];

echo $view->render('@install/step-4.twig', $viewData + ['data' => $data]);
