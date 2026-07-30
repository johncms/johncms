<?php

/**
 * This file is part of JohnCMS Content Management System.
 *
 * @copyright JohnCMS Community
 * @license   https://opensource.org/licenses/GPL-3.0 GPL-3.0
 * @link      https://johncms.com JohnCMS Project
 */

declare(strict_types=1);

use Johncms\Console\Commands\CacheClearCommand;
use Johncms\Modules\Admin\Application\UseCases\RebuildSmiliesCacheUseCase;
use Johncms\Modules\Admin\Domain\Services\LanguageFilesManagerInterface;
use Johncms\Modules\ModuleInstaller;
use Johncms\Modules\Modules;
use Johncms\Http\Request;
use Johncms\Users\User;
use Johncms\Validator\Validator;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\NullOutput;

/** @var Request $request Built by the installer entry point, which includes this file. */

di(PDO::class);

$view->addData(
    [
        'title'      => __('Setting'),
        'page_title' => __('Setting'),
    ]
);

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
    // Настройки валидатора
    $rules = [
        'homeurl'        => [
            'NotEmpty',
        ],
        'email'          => [
            'EmailAddress' => [
                'allow'          => Laminas\Validator\Hostname::ALLOW_DNS,
                'useMxCheck'     => true,
                'useDeepMxCheck' => true,
            ],
        ],
        'admin_login'    => [
            'NotEmpty',
        ],
        'admin_password' => [
            'NotEmpty',
        ],
    ];
    // Валидация
    $validator = new Validator($fields, $rules);
    if ($validator->isValid()) {
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
            // Регистрируем пользователя
            $user = (new User())->create(
                [
                    'name'            => $fields['admin_login'],
                    'name_lat'        => mb_strtolower($fields['admin_login']),
                    'password'        => md5(md5($fields['admin_password'])),
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
            // Устанавливаем сессию и COOKIE c данными администратора
            $_SESSION['uid'] = $user->id;
            $_SESSION['ups'] = md5($fields['admin_password']);
            setcookie('cuid', (string) $user->id, time() + 3600 * 24 * 365, '/');
            setcookie('cups', md5($fields['admin_password']), time() + 3600 * 24 * 365, '/');

            if (! empty($fields['install_demo'])) {
                // Seed a couple of regular users referenced by module demo data (authors, commenters).
                $demoUsers = [
                    ['name' => 'Alex', 'sex' => 'm', 'mail' => 'alex@example.com', 'ip' => '192.0.2.10'],
                    ['name' => 'Maria', 'sex' => 'f', 'mail' => 'maria@example.com', 'ip' => '192.0.2.20'],
                ];
                foreach ($demoUsers as $demoUser) {
                    (new User())->create(
                        [
                            'name'            => $demoUser['name'],
                            'name_lat'        => mb_strtolower($demoUser['name']),
                            'password'        => md5(md5('demo')),
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
                }

                $modules = new Modules();
                $modules->registerAutoloader();
                foreach ($modules->getInstalled() as $module) {
                    (new ModuleInstaller($module))->installDemoData();
                }
            }

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
        $errors = $validator->getErrors();
    }
}

$data = [
    'errors'             => $errors,
    'fields'             => $fields,
    'next_step_disabled' => false,
];

echo $view->render('install::step_4', ['data' => $data]);
