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
use Johncms\Mail\EmailMessage;
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
/** @var Translator $translator */
$translator = di(Translator::class);
$translator->addTranslationDomain('registration', __DIR__ . '/locale');

$nav_chain->add(__('Registration'));

// Email confirmation
$action = $request->getQuery('act', '');
$id = $request->getQuery('id', 0, FILTER_VALIDATE_INT);
if ($action === 'confirm_email' && ! empty($id)) {
    $code = $request->getQuery('code', '');
    $confirm_user = (new User())->find($id);
    if ($confirm_user !== null && ! $confirm_user->email_confirmed && $confirm_user->confirmation_code === $code) {
        $confirm_user->email_confirmed = true;
        $confirm_user->confirmation_code = null;
        $confirm_user->save();
    }

    echo $view->render('reg::email_confirmed', ['confirm_user' => $confirm_user]);
    exit;
}

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

    if (! empty($config['user_email_required']) || ! empty($config['user_email_confirmation'])) {
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

                'email_confirmed'   => ! empty($config['user_email_confirmation']) ? null : 1,
                'confirmation_code' => ! empty($config['user_email_confirmation']) ? uniqid('email_', true) : null,
            ]
        );

        if ($config['user_email_confirmation']) {
            $link = $config['homeurl'] . '/registration/?act=confirm_email&id=' . $new_user->id . '&code=' . $new_user->confirmation_code;
            $name = ! empty($new_user->imname) ? htmlspecialchars($new_user->imname) : $new_user->name;
            (new EmailMessage())->create(
                [
                    'priority' => 1,
                    'locale'   => $translator->getLocale(),
                    'template' => 'system::mail/templates/registration',
                    'fields'   => [
                        'email_to'        => $new_user->mail,
                        'name_to'         => $name,
                        'subject'         => __('Registration on the website'),
                        'user_name'       => $name,
                        'user_login'      => $new_user->name,
                        'link_to_confirm' => $link,
                    ],
                ]
            );
        }

        if ($config['mod_reg'] !== 1 && empty($config['user_email_confirmation'])) {
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
