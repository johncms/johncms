<?php

/**
 * This file is part of JohnCMS Content Management System.
 *
 * @copyright JohnCMS Community
 * @license   https://opensource.org/licenses/GPL-3.0 GPL-3.0
 * @link      https://johncms.com JohnCMS Project
 */

declare(strict_types=1);

use Illuminate\Support\Str;
use Johncms\System\Http\Request;
use Johncms\System\Legacy\Tools;
use Johncms\Users\User;
use Johncms\System\View\Render;
use Johncms\NavChain;
use Johncms\System\i18n\Translator;
use Johncms\Validator\Validator;

defined('_IN_JOHNCMS') || die('Error: restricted access');

$config = di('config')['johncms'];

/** @var Tools $tools */
$tools = di(Tools::class);

/** @var User $user */
$user = di(User::class);

/** @var Render $view */
$view = di(Render::class);

/** @var NavChain $nav_chain */
$nav_chain = di(NavChain::class);

/** @var Request $request */
$request = di(Request::class);

// Регистрируем Namespace для шаблонов модуля
$view->addFolder('reg', __DIR__ . '/templates/');

// Register the module languages domain and folder
di(Translator::class)->addTranslationDomain('registration', __DIR__ . '/locale');

$nav_chain->add(__('Registration'));

// Если регистрация закрыта, выводим предупреждение
if (! $config['mod_reg'] || $user->isValid()) {
    echo $view->render('reg::registration_closed', []);
    exit;
}

$fields = [
    'name'     => $request->getPost('name', '', FILTER_SANITIZE_STRING),
    'name_lat' => Str::slug($request->getPost('name', '', FILTER_SANITIZE_STRING), '_'),
    'password' => $request->getPost('password', ''),
    'sex'      => $request->getPost('sex', ''),
    'imname'   => $request->getPost('imname', '', FILTER_SANITIZE_STRING),
    'about'    => $request->getPost('about', '', FILTER_SANITIZE_STRING),
    'captcha'  => $request->getPost('captcha', null),
    'email'    => $request->getPost('email', ''),
];

$errors = [];
if ($request->getMethod() === 'POST') {
    $rules = [
        'name'     => [
            'NotEmpty',
            'StringLength'   => ['min' => 2, 'max' => 20],
            'ModelNotExists' => [
                'model' => User::class,
                'field' => 'name',
            ],
        ],
        'name_lat' => [
            'ModelNotExists' => [
                'model' => User::class,
                'field' => 'name_lat',
            ],
        ],
        'password' => [
            'NotEmpty',
            'StringLength' => ['min' => 6],
        ],
        'sex'      => [
            'InArray' => ['haystack' => ['m', 'zh']],
        ],
        'captcha'  => ['Captcha'],
    ];

    if (! empty($config['user_email_required'])) {
        $rules['email'] = [
            'EmailAddress'   => [
                'allow'          => Laminas\Validator\Hostname::ALLOW_DNS,
                'useMxCheck'     => true,
                'useDeepMxCheck' => true,
            ],
            'ModelNotExists' => [
                'model' => User::class,
                'field' => 'mail',
            ],
        ];
    }

    $validator = new Validator($fields, $rules);
    if ($validator->isValid()) {
        /** @var Johncms\System\Http\Environment $env */
        $env = di(Johncms\System\Http\Environment::class);

        $new_user = (new User())->create(
            [
                'name'         => $fields['name'],
                'name_lat'     => $fields['name_lat'],
                'password'     => md5(md5($fields['password'])),
                'imname'       => $fields['imname'],
                'about'        => $fields['about'],
                'sex'          => $fields['sex'],
                'mail'         => $fields['email'],
                'rights'       => 0,
                'ip'           => $env->getIp(false),
                'ip_via_proxy' => $env->getIpViaProxy(false),
                'browser'      => $env->getUserAgent(),
                'datereg'      => time(),
                'lastdate'     => time(),
                'sestime'      => time(),
                'preg'         => $config['mod_reg'] > 1 ? 1 : 0,
                'set_user'     => [],
                'set_forum'    => [],
                'set_mail'     => [],
                'smileys'      => [],
            ]
        );

        if ($config['mod_reg'] !== 1) {
            setcookie('cuid', (string) $new_user->id, time() + 3600 * 24 * 365, '/');
            setcookie('cups', md5($fields['password']), time() + 3600 * 24 * 365, '/');
        }

        echo $view->render(
            'reg::registration_result',
            [
                'usid'     => $new_user->id,
                'reg_nick' => $fields['name'],
                'reg_pass' => $fields['password'],
            ]
        );
        exit;
    }

    $errors = $validator->getErrors();
    unset($_SESSION['code']);
}

// Форма регистрации
$code = (string) new Mobicms\Captcha\Code();
$_SESSION['code'] = $code;

echo $view->render(
    'reg::index',
    [
        'errors'  => $errors,
        'fields'  => $fields,
        'captcha' => new Mobicms\Captcha\Image($code),
    ]
);
