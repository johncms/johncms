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

$title = __('Edit Profile');
$data = [];

// Проверяем права доступа для редактирования Профиля
if ($user_data['id'] !== $user->id && ($user->rights < 7 || $user_data['rights'] >= $user->rights)) {
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
$nav_chain->add(($user_data['id'] !== $user->id ? __('Profile') : __('My Profile')), '?user=' . $user_data['id']);
$nav_chain->add($title);

$data['back_url'] = '?user=' . $user_data['id'];

if (isset($_GET['delavatar'])) {
    // Удаляем аватар
    $avatar = UPLOAD_PATH . 'users/avatar/' . $user_data['id'] . '.png';
    @unlink($avatar);
    $data['success_message'] = __('Avatar is successfully removed');
} elseif (isset($_GET['delphoto'])) {
    // Удаляем фото
    $photo = UPLOAD_PATH . 'users/photo/' . $user_data['id'] . '.jpg';
    $small_photo = UPLOAD_PATH . 'users/photo/' . $user_data['id'] . '_small.jpg';
    @unlink($photo);
    @unlink($small_photo);
    $data['success_message'] = __('Photo is successfully removed');
} elseif (isset($_POST['submit'])) {
    // Принимаем данные из формы, проверяем и записываем в базу
    $error = [];
    $user_data['imname'] = isset($_POST['imname']) ? htmlspecialchars(mb_substr(trim($_POST['imname']), 0, 25)) : '';
    $user_data['live'] = isset($_POST['live']) ? htmlspecialchars(mb_substr(trim($_POST['live']), 0, 50)) : '';
    $user_data['dayb'] = isset($_POST['dayb']) ? (int) ($_POST['dayb']) : 0;
    $user_data['monthb'] = isset($_POST['monthb']) ? (int) ($_POST['monthb']) : 0;
    $user_data['yearofbirth'] = isset($_POST['yearofbirth']) ? (int) ($_POST['yearofbirth']) : 0;
    $user_data['about'] = isset($_POST['about']) ? htmlspecialchars(mb_substr(trim($_POST['about']), 0, 500)) : '';
    $user_data['mibile'] = isset($_POST['mibile']) ? htmlspecialchars(mb_substr(trim($_POST['mibile']), 0, 40)) : '';
    $user_data['mail'] = isset($_POST['mail']) ? htmlspecialchars(mb_substr(trim($_POST['mail']), 0, 40)) : '';
    $user_data['mailvis'] = isset($_POST['mailvis']) ? 1 : 0;
    $user_data['icq'] = isset($_POST['icq']) ? (int) ($_POST['icq']) : 0;
    $user_data['skype'] = isset($_POST['skype']) ? htmlspecialchars(mb_substr(trim($_POST['skype']), 0, 40)) : '';
    $user_data['jabber'] = isset($_POST['jabber']) ? htmlspecialchars(mb_substr(trim($_POST['jabber']), 0, 40)) : '';
    $user_data['www'] = isset($_POST['www']) ? htmlspecialchars(mb_substr(trim($_POST['www']), 0, 40)) : '';
    // Данные юзера (для Администраторов)
    $user_data['name'] = isset($_POST['name']) ? htmlspecialchars(mb_substr(trim($_POST['name']), 0, 20)) : $user_data['name'];
    $user_data['status'] = isset($_POST['status']) ? htmlspecialchars(mb_substr(trim($_POST['status']), 0, 50)) : '';
    $user_data['karma_off'] = isset($_POST['karma_off']) ? 1 : 0;
    $user_data['sex'] = isset($_POST['sex']) && $_POST['sex'] === 'm' ? 'm' : 'zh';
    $user_data['rights'] = isset($_POST['rights']) ? abs((int) ($_POST['rights'])) : $user_data['rights'];

    // Проводим необходимые проверки
    if ($user_data['rights'] > $user->rights || $user_data['rights'] > 9 || $user_data['rights'] < 0) {
        $user_data['rights'] = 0;
    }

    if ($user->rights >= 7) {
        if (mb_strlen($user_data['name']) < 2 || mb_strlen($user_data['name']) > 20) {
            $error[] = __('Min. nick length 2, max. 20 characters');
        }

        $lat_nick = $tools->rusLat($user_data['name']);

        if (preg_match("/[^0-9a-z\-\@\*\(\)\?\!\~\_\=\[\]]+/", $lat_nick)) {
            $error[] = __('Nick contains invalid characters');
        }
    }
    if ($user_data['dayb'] || $user_data['monthb'] || $user_data['yearofbirth']) {
        if ($user_data['dayb'] < 1 || $user_data['dayb'] > 31 || $user_data['monthb'] < 1 || $user_data['monthb'] > 12) {
            $error[] = __('Invalid format date of birth');
        }
    }

    if ($user_data['icq'] && ($user_data['icq'] < 10000 || $user_data['icq'] > 999999999)) {
        $error[] = __('ICQ number must be at least 5 digits and max. 10');
    }

    if (! $error) {
        $stmt = $db->prepare(
            'UPDATE `users` SET
          `imname` = ?,
          `live` = ?,
          `dayb` = ?,
          `monthb` = ?,
          `yearofbirth` = ?,
          `about` = ?,
          `mibile` = ?,
          `mail` = ?,
          `mailvis` = ?,
          `icq` = ?,
          `skype` = ?,
          `jabber` = ?,
          `www` = ?
          WHERE `id` = ?
        '
        );

        $stmt->execute(
            [
                $user_data['imname'],
                $user_data['live'],
                $user_data['dayb'],
                $user_data['monthb'],
                $user_data['yearofbirth'],
                $user_data['about'],
                $user_data['mibile'],
                $user_data['mail'],
                $user_data['mailvis'],
                $user_data['icq'],
                $user_data['skype'],
                $user_data['jabber'],
                $user_data['www'],
                $user_data['id'],
            ]
        );

        if ($user->rights >= 7) {
            $stmt = $db->prepare(
                'UPDATE `users` SET
              `name` = ?,
              `status` = ?,
              `karma_off` = ?,
              `sex` = ?,
              `rights` = ?
              WHERE `id` = ?
            '
            );

            $stmt->execute(
                [
                    $user_data['name'],
                    $user_data['status'],
                    $user_data['karma_off'],
                    $user_data['sex'],
                    $user_data['rights'],
                    $user_data['id'],
                ]
            );
        }
        $_SESSION['success_message'] = __('Data saved');
    } else {
        $_SESSION['edit_errors'] = $error;
    }

    header('Location: ?act=edit&user=' . $user_data['id']);
    exit;
}

$data['form_action'] = '?act=edit&amp;user=' . $user_data['id'];
$user_data['about'] = htmlspecialchars($user_data['about']);

if (! empty($_SESSION['edit_errors'])) {
    $data['errors'] = $_SESSION['edit_errors'];
    unset($_SESSION['edit_errors']);
}

if (! empty($_SESSION['success_message'])) {
    $data['success_message'] = $_SESSION['success_message'];
    unset($_SESSION['success_message']);
}

$data['user'] = $user_data;

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
