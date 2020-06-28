<?php

/**
 * This file is part of JohnCMS Content Management System.
 *
 * @copyright JohnCMS Community
 * @license   https://opensource.org/licenses/GPL-3.0 GPL-3.0
 * @link      https://johncms.com JohnCMS Project
 */

declare(strict_types=1);

use Johncms\Mail\EmailMessage;
use Johncms\System\Http\Request;
use Johncms\Users\User;
use Johncms\Validator\Validator;

defined('_IN_JOHNCMS') || die('Error: restricted access');

$title = __('Edit Profile');
$data = [
    'errors' => [],
];

/** @var Request $request */
$request = di(Request::class);

// Проверяем права доступа для редактирования Профиля
if ($user_data->id !== $user->id && ($user->rights < 7 || $user_data->rights > $user->rights)) {
    echo $view->render(
        'system::pages/result',
        [
            'title'   => $title,
            'type'    => 'alert-danger',
            'message' => __('You cannot edit profile of higher administration'),
        ]
    );
    exit;
}

if (! empty($user->ban)) {
    echo $view->render(
        'system::pages/result',
        [
            'title'   => $title,
            'type'    => 'alert-danger',
            'message' => __('Access forbidden'),
        ]
    );
    exit;
}
$nav_chain->add(($user_data->id !== $user->id ? __('Profile') : __('My Profile')), '?user=' . $user_data->id);
$nav_chain->add($title);

$data['back_url'] = '?user=' . $user_data->id;

// Готовим массив с данными пользователя
$form_data = [
    'imname'      => $request->getPost('imname', $user_data->imname, FILTER_SANITIZE_FULL_SPECIAL_CHARS),
    'live'        => $request->getPost('live', $user_data->live, FILTER_SANITIZE_FULL_SPECIAL_CHARS),
    'dayb'        => $request->getPost('dayb', $user_data->dayb, FILTER_SANITIZE_STRING),
    'monthb'      => $request->getPost('monthb', $user_data->monthb, FILTER_SANITIZE_STRING),
    'yearofbirth' => $request->getPost('yearofbirth', $user_data->yearofbirth, FILTER_SANITIZE_STRING),
    'about'       => $request->getPost('about', $user_data->about, FILTER_SANITIZE_FULL_SPECIAL_CHARS),
    'mibile'      => $request->getPost('mibile', $user_data->mibile, FILTER_SANITIZE_FULL_SPECIAL_CHARS),
    'mail'        => $request->getPost('mail', $user_data->mail, FILTER_SANITIZE_FULL_SPECIAL_CHARS),
    'mailvis'     => $request->getPost('mailvis', $user_data->mailvis, FILTER_VALIDATE_INT),
    'skype'       => $request->getPost('skype', $user_data->skype, FILTER_SANITIZE_FULL_SPECIAL_CHARS),
    'jabber'      => $request->getPost('jabber', $user_data->jabber, FILTER_SANITIZE_FULL_SPECIAL_CHARS),
    'www'         => $request->getPost('www', $user_data->www, FILTER_SANITIZE_FULL_SPECIAL_CHARS),
    'status'      => $request->getPost('status', $user_data->status, FILTER_SANITIZE_FULL_SPECIAL_CHARS),

    // Данные юзера (для Администраторов)
    'name'        => $request->getPost('name', $user_data->name, FILTER_SANITIZE_FULL_SPECIAL_CHARS),
    'karma_off'   => $request->getPost('karma_off', $user_data->karma_off, FILTER_VALIDATE_INT),
    'sex'         => $request->getPost('sex', $user_data->sex, FILTER_SANITIZE_FULL_SPECIAL_CHARS),
    'rights'      => $request->getPost('rights', $user_data->rights, FILTER_VALIDATE_INT),
    'csrf_token'  => $request->getPost('csrf_token', ''),
    'admin_notes' => $request->getPost('admin_notes', $user_data->admin_notes),
];

if (isset($_GET['delavatar'])) {
    // Удаляем аватар
    $avatar = UPLOAD_PATH . 'users/avatar/' . $user_data->id . '.png';
    @unlink($avatar);
    $data['success_message'] = __('Avatar is successfully removed');
} elseif (isset($_GET['delphoto'])) {
    // Удаляем фото
    $photo = UPLOAD_PATH . 'users/photo/' . $user_data->id . '.jpg';
    $small_photo = UPLOAD_PATH . 'users/photo/' . $user_data->id . '_small.jpg';
    @unlink($photo);
    @unlink($small_photo);
    $data['success_message'] = __('Photo is successfully removed');
} elseif (isset($_POST['submit'])) {
    // Принимаем данные из формы, проверяем и записываем в базу
    $error = [];

    // Правила валидации
    $validation_rules = [
        'imname'      => [
            'NotEmpty',
            'StringLength' => ['max' => 100],
        ],
        'live'        => ['StringLength' => ['max' => 100]],
        'dayb'        => ['Between' => ['min' => 1, 'max' => 31]],
        'monthb'      => ['Between' => ['min' => 1, 'max' => 12]],
        'yearofbirth' => ['Between' => ['min' => 1900, 'max' => 3000]],
        'mibile'      => ['StringLength' => ['max' => 50]],
        'skype'       => ['StringLength' => ['max' => 50]],
        'jabber'      => ['StringLength' => ['max' => 50]],
        'www'         => ['StringLength' => ['max' => 50]],
        'mail'        => [
            'ModelNotExists' => [
                'model'   => User::class,
                'field'   => 'mail',
                'exclude' => static function ($query) use ($user_data) {
                    return $query->where('mail', '!=', '')->where('id', '!=', $user_data->id);
                },
            ],
        ],
        'sex'         => ['InArray' => ['haystack' => ['m', 'zh']]],
        'csrf_token'  => ['Csrf'],
    ];

    // Если включено подтверждение e-mail, делаем его обязательным
    if (! empty($config['user_email_confirmation'])) {
        $validation_rules['mail'][] = 'NotEmpty';
        $validation_rules['mail']['EmailAddress'] = [
            'allow'          => Laminas\Validator\Hostname::ALLOW_DNS,
            'useMxCheck'     => true,
            'useDeepMxCheck' => true,
        ];
    }

    // Проводим необходимые проверки
    if ($form_data['rights'] > $user->rights || $form_data['rights'] > 9 || $form_data['rights'] < 0) {
        $form_data['rights'] = 0;
    }

    if ($user->rights >= 7) {
        $validation_rules['name'] = ['StringLength' => ['min' => 2, 'max' => 25]];
    }

    // Проверяем данные
    $validator = new Validator($form_data, $validation_rules);
    if ($validator->isValid()) {
        // Обновляем пользовательские данные
        if ($user->rights < 7) {
            unset($form_data['name'], $form_data['karma_off'], $form_data['sex'], $form_data['rights'], $form_data['admin_notes']);
        }

        // Если включено подтверждение e-mail адреса, то при его изменении необходимо выполнить подтверждение
        if (! empty($config['user_email_confirmation']) && $user_data->mail !== $form_data['mail'] && $user->rights < 7) {
            // Новый e-mail записываем в отдельное поле, старый сохраняем, генерируем код подтверждения
            $form_data['new_email'] = $form_data['mail'];
            $form_data['mail'] = $user_data->mail;
            $form_data['confirmation_code'] = uniqid('email_', true);

            // Отправляем письмо на новый email
            $link = $config['homeurl'] . '/profile/?act=confirm_new_email&id=' . $user_data->id . '&code=' . $form_data['confirmation_code'];
            (new EmailMessage())->create(
                [
                    'priority' => 1,
                    'locale'   => $translator->getLocale(),
                    'template' => 'system::mail/templates/confirm_email_change',
                    'fields'   => [
                        'email_to'        => $form_data['new_email'],
                        'name_to'         => $user_data->name,
                        'subject'         => __('Confirm email change'),
                        'user_name'       => $user_data->name,
                        'link_to_confirm' => $link,
                    ],
                ]
            );

            // Отправляем уведомление о начале процедуры изменения на старый email
            if (! empty($user_data->mail)) {
                (new EmailMessage())->create(
                    [
                        'priority' => 1,
                        'locale'   => $translator->getLocale(),
                        'template' => 'system::mail/templates/changed_email_notification',
                        'fields'   => [
                            'email_to'  => $user_data->mail,
                            'name_to'   => $user_data->name,
                            'subject'   => __('The procedure for changing the email address was started'),
                            'user_name' => $user_data->name,
                            'new_email' => $form_data['new_email'],
                        ],
                    ]
                );
            }
        }

        $user_data->update($form_data);
        $_SESSION['success_message'] = __('Data saved');
        header('Location: ?act=edit&user=' . $user_data->id);
        exit;
    }

    $data['errors'] = $validator->getErrors();
}

$data['form_action'] = '?act=edit&amp;user=' . $user_data->id;

if (! empty($_SESSION['success_message'])) {
    $data['success_message'] = $_SESSION['success_message'];
    unset($_SESSION['success_message']);
}

$data['user'] = $user_data->toArray();
$data['form_data'] = $form_data;

$avatar = UPLOAD_PATH . 'users/avatar/' . $user_data['id'] . '.png';
if (file_exists($avatar)) {
    $data['user']['avatar_file'] = $avatar;
    $data['delete_avatar_url'] = '?act=edit&amp;user=' . $user_data['id'] . '&amp;delavatar';
}

echo $view->render(
    'profile::edit',
    [
        'title'      => $title,
        'page_title' => $title,
        'data'       => $data,
    ]
);
