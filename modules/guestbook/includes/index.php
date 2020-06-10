<?php

/**
 * This file is part of JohnCMS Content Management System.
 *
 * @copyright JohnCMS Community
 * @license   https://opensource.org/licenses/GPL-3.0 GPL-3.0
 * @link      https://johncms.com JohnCMS Project
 */

declare(strict_types=1);

use Guestbook\Models\Guestbook;
use Johncms\System\Http\Environment;
use Johncms\System\Http\Request;
use Johncms\System\Legacy\Bbcode;
use Johncms\Users\User;
use Johncms\Validator\Validator;

/** @var User $user */
$user = di(User::class);

/** @var Request $request */
$request = di(Request::class);

/** @var Environment $env */
$env = di(Environment::class);

/** @var Bbcode $bbcode */
$bbcode = di(Bbcode::class);

$data = [
    'access_to_buttons' => ($user->rights > 0 || in_array($user->id, $guestAccess)),
    'is_guestbook'      => ! isset($_SESSION['ga']),
    'access_to_form'    => ($user->isValid() || $config['mod_guest'] === 2) && ! isset($user->ban['1']) && ! isset($user->ban['13']),
    'bbcode'            => $bbcode->buttons('form', 'message'),
    'errors'            => [],
];

$form_data = [
    'name'       => $request->getPost('name', '', FILTER_SANITIZE_STRING),
    'message'    => $request->getPost('message', ''),
    'csrf_token' => $request->getPost('csrf_token', ''),
    'code'       => $request->getPost('code', ''),
];

$form_data = array_map('trim', $form_data);
$data['form_data'] = $form_data;

if ($request->getMethod() === 'POST') {
    $messages = [
        'isEmpty'              => __('Value is required and can\'t be empty'),
        'stringLengthTooShort' => __('The input is less than %min% characters long'),
        'stringLengthTooLong'  => __('The input is more than %max% characters long'),
    ];

    $rules = [
        'message'    => [
            'NotEmpty',
            'StringLength'   => ['min' => 4],
            'ModelNotExists' => [
                'model'   => Guestbook::class,
                'field'   => 'text',
                'exclude' => static function ($query) use ($user) {
                    $query->where('user_id', $user->id)->where('time', '>', (time() - 600));
                },
            ],
        ],
        'csrf_token' => [
            'Csrf',
            'Flood',
            'Ban' => [
                'bans' => [1, 13],
            ],
        ],
    ];

    if (! $user->isValid()) {
        $rules['name'] = [
            'NotEmpty',
            'StringLength' => ['min' => 3, 'max' => 25],
        ];
        $rules['code'] = [
            'Captcha',
        ];
    }

    $validator = new Validator($form_data, $rules, $messages);

    if ($validator->isValid()) {
        $new_message = (new Guestbook())->create(
            [
                'adm'     => ! $data['is_guestbook'],
                'time'    => time(),
                'user_id' => $user->id ?? 0,
                'name'    => $user->isValid() ? $user->name : $form_data['name'],
                'text'    => $form_data['message'],
                'ip'      => $env->getIp(false),
                'browser' => $env->getUserAgent(),
                'otvet'   => '',
            ]
        );
        if ($user->isValid()) {
            $post_guest = $user->postguest + 1;
            (new User())
                ->where('id', $user->id)
                ->update(
                    [
                        'postguest' => $post_guest,
                        'lastpost'  => time(),
                    ]
                );
        }
        $data['form_data']['message'] = '';
    } else {
        $data['errors'] = $validator->getErrors();
    }
    unset($_SESSION['code']);
}

if ($data['access_to_form'] && ! $user->isValid()) {
    // CAPTCHA for guests
    $code = (new Mobicms\Captcha\Code())->generate();
    $_SESSION['code'] = $code;
    $data['captcha'] = (new Mobicms\Captcha\Image($code))->generate();
}

$admin_club = (isset($_SESSION['ga']) && ($user->rights >= 1 || in_array($user->id, $guestAccess)));
$messages = (new Guestbook())->with('user')->where('adm', $admin_club)->orderByDesc('time')->paginate($user->config->kmess);

$data['items'] = $messages;
$data['pagination'] = $messages->render();

echo $view->render(
    'guestbook::index',
    [
        'title' => $title,
        'data'  => $data,
    ]
);
