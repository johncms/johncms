<?php

/**
 * This file is part of JohnCMS Content Management System.
 *
 * @copyright JohnCMS Community
 * @license   https://opensource.org/licenses/GPL-3.0 GPL-3.0
 * @link      https://johncms.com JohnCMS Project
 */

declare(strict_types=1);

use Admin\Languages\Languages;
use Install\Database;
use Johncms\System\Http\Request;
use Johncms\Users\User;
use Johncms\Validator\Validator;

module_lib_loader('admin');

/** @var Request $request */
$request = di(Request::class);

di(PDO::class);

$view->addData(
    [
        'title'      => __('Setting'),
        'page_title' => __('Setting'),
    ]
);

$fields = [
    'homeurl'        => $request->getPost('homeurl', ($request->isHttps() ? 'https://' : 'http://') . $_SERVER['SERVER_NAME'], FILTER_SANITIZE_URL),
    'email'          => $request->getPost('email'),
    'admin_login'    => $request->getPost('admin_login', 'admin', FILTER_SANITIZE_STRING),
    'admin_password' => $request->getPost('admin_password', '', FILTER_SANITIZE_SPECIAL_CHARS),
    'install_demo'   => $request->getPost('install_demo', 0, FILTER_VALIDATE_INT),
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
        $config = di('config')['johncms'];

        // Изменяем некоторые параметры
        $config['homeurl'] = $fields['homeurl'];
        $config['email'] = $fields['email'];
        $config['lng'] = $translator->getLocale();
        $config['lng_list'] = Languages::getLngList();

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
                    'ip'              => ip2long($_SERVER['REMOTE_ADDR']),
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

echo $view->render('install::step_4', ['data' => $data]);
