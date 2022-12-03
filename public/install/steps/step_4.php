<?php

/**
 * This file is part of JohnCMS Content Management System.
 *
 * @copyright JohnCMS Community
 * @license   https://opensource.org/licenses/GPL-3.0 GPL-3.0
 * @link      https://johncms.com JohnCMS Project
 */

declare(strict_types=1);

use Johncms\Http\Request;
use Johncms\i18n\Languages;
use Johncms\i18n\Translator;
use Johncms\Install\Database;
use Johncms\Users\AuthProviders\SessionAuthProvider;
use Johncms\Users\Role;
use Johncms\Users\UserManager;
use Johncms\Validator\Validator;
use Johncms\View\Render;

$request = di(Request::class);
$render = di(Render::class);
$translator = di(Translator::class);

di(PDO::class);

$render->addData(
    [
        'title'      => __('Setting'),
        'page_title' => __('Setting'),
    ]
);

$fields = [
    'home_url'       => $request->getPost('home_url', ($request->isHttps() ? 'https://' : 'http://') . $_SERVER['SERVER_NAME'], FILTER_SANITIZE_URL),
    'email'          => $request->getPost('email'),
    'admin_login'    => $request->getPost('admin_login', 'admin'),
    'admin_password' => $request->getPost('admin_password', ''),
    'install_demo'   => $request->getPost('install_demo', 0, FILTER_VALIDATE_INT),
];

$errors = [];

if ($request->getMethod() === 'POST') {
    // Настройки валидатора
    $rules = [
        'home_url'       => [
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
        $config = di('config')['johncms'];

        // Изменяем некоторые параметры
        $config['home_url'] = $fields['home_url'];
        $config['email'] = $fields['email'];
        $config['language'] = $translator->getLocale();
        $config['lng_list'] = Languages::getLngList();

        $system_settings = [
            'johncms' => $config,
        ];
        $configFile = "<?php\n\n" . 'return ' . var_export($system_settings, true) . ";\n";

        if (file_put_contents(CONFIG_PATH . 'autoload/system.local.php', $configFile)) {
            // Регистрируем пользователя
            $userManager = di(UserManager::class);
            $createdUser = $userManager->create(
                [
                    'login'           => $fields['admin_login'],
                    'password'        => $fields['admin_password'],
                    'email'           => $fields['email'],
                    'confirmed'       => true,
                    'email_confirmed' => true,
                    'settings'        => [
                        'lang' => $translator->getLocale(),
                    ],
                ]
            );

            // Attach the admin role to the user
            $adminRole = (new Role())->where('name', 'admin')->first();
            $createdUser->roles()->attach($adminRole->id);

            // Authorize the user
            $sessionProvider = di(SessionAuthProvider::class);
            $sessionProvider->store($createdUser);

            if (! empty($fields['install_demo'])) {
                Database::installDemo();
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

echo $render->render('install::step_4', ['data' => $data]);
