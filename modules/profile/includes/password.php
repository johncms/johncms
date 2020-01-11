<?php

/**
 * This file is part of JohnCMS Content Management System.
 *
 * @copyright JohnCMS Community
 * @license   https://opensource.org/licenses/GPL-3.0 GPL-3.0
 * @link      https://johncms.com JohnCMS Project
 */

declare(strict_types=1);

defined('_IN_JOHNCMS') || die('Error: restricted access');

$title = htmlspecialchars($foundUser->name) . ': ' . __('Change Password');
$nav_chain->add(__('Change Password'));

// Проверяем права доступа
if ($foundUser->id !== $user->id && ($user->rights < 7 || $foundUser->rights > $user->rights)) {
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

$data = [];

if ($mod === 'change') {
    // Меняем пароль
    $error = [];
    $oldpass = isset($_POST['oldpass']) ? trim($_POST['oldpass']) : '';
    $newpass = isset($_POST['newpass']) ? trim($_POST['newpass']) : '';
    $newconf = isset($_POST['newconf']) ? trim($_POST['newconf']) : '';
    $autologin = isset($_POST['autologin']) ? 1 : 0;

    if ($foundUser->id !== $user->id) {
        if (! $newpass || ! $newconf) {
            $error[] = __('It is necessary to fill in all fields');
        }
    } elseif (! $oldpass || ! $newpass || ! $newconf) {
        $error[] = __('It is necessary to fill in all fields');
    }


    if (! $error && $foundUser->id === $user->id && md5(md5($oldpass)) !== $foundUser->password) {
        $error[] = __('Old password entered incorrectly');
    }

    if ($newpass !== $newconf) {
        $error[] = __('The password confirmation you entered is wrong');
    }

    if (! $error && (strlen($newpass) < 3)) {
        $error[] = __('The password must contain at least 3 characters');
    }

    if (! $error) {
        // Записываем в базу
        $db->prepare('UPDATE `users` SET `password` = ? WHERE `id` = ?')->execute(
            [
                md5(md5($newpass)),
                $foundUser->id,
            ]
        );

        // Проверяем и записываем COOKIES
        if ($user->id === $foundUser->id && isset($_COOKIE['cuid'], $_COOKIE['cups'])) {
            setcookie('cups', md5($newpass), time() + 3600 * 24 * 365);
        }

        echo $view->render(
            'system::pages/result',
            [
                'title'         => $title,
                'type'          => 'alert-success',
                'message'       => __('Password successfully changed'),
                'back_url'      => ($user->id === $foundUser->id ? '/login' : '?user=' . $foundUser->id),
                'back_url_name' => __('Continue'),
            ]
        );
    } else {
        echo $view->render(
            'system::pages/result',
            [
                'title'         => $title,
                'type'          => 'alert-danger',
                'message'       => $error,
                'back_url'      => '?act=password&amp;user=' . $foundUser->id,
                'back_url_name' => __('Repeat'),
            ]
        );
    }
} else {
    $data['form_action'] = '?act=password&amp;mod=change&amp;user=' . $foundUser->id;
    $data['show_old_password_field'] = $foundUser->id === $user->id;
    $data['back_url'] = '?user=' . $foundUser->id;

    echo $view->render(
        'profile::password',
        [
            'title'      => $title,
            'page_title' => $title,
            'data'       => $data,
        ]
    );
}
