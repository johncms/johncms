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

$foundUser = (array) $foundUser;
$title = __('Edit Profile');
$data = [];

// Проверяем права доступа для редактирования Профиля
if ($foundUser['id'] !== $user->id && ($user->rights < 7 || $foundUser['rights'] >= $user->rights)) {
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
$nav_chain->add(($foundUser['id'] !== $user->id ? __('Profile') : __('My Profile')), '?user=' . $foundUser['id']);
$nav_chain->add($title);

$data['back_url'] = '?user=' . $foundUser['id'];

if (isset($_GET['delavatar'])) {
    // Удаляем аватар
    $avatar = UPLOAD_PATH . 'users/avatar/' . $foundUser['id'] . '.png';
    @unlink($avatar);
    $data['success_message'] = __('Avatar is successfully removed');
} elseif (isset($_GET['delphoto'])) {
    // Удаляем фото
    $photo = UPLOAD_PATH . 'users/photo/' . $foundUser['id'] . '.jpg';
    $small_photo = UPLOAD_PATH . 'users/photo/' . $foundUser['id'] . '_small.jpg';
    @unlink($photo);
    @unlink($small_photo);
    $data['success_message'] = __('Photo is successfully removed');
} elseif (isset($_POST['submit'])) {
    // Принимаем данные из формы, проверяем и записываем в базу
    $error = [];
    $foundUser['imname'] = isset($_POST['imname']) ? htmlspecialchars(mb_substr(trim($_POST['imname']), 0, 25)) : '';
    $foundUser['live'] = isset($_POST['live']) ? htmlspecialchars(mb_substr(trim($_POST['live']), 0, 50)) : '';
    $foundUser['dayb'] = isset($_POST['dayb']) ? (int) ($_POST['dayb']) : 0;
    $foundUser['monthb'] = isset($_POST['monthb']) ? (int) ($_POST['monthb']) : 0;
    $foundUser['yearofbirth'] = isset($_POST['yearofbirth']) ? (int) ($_POST['yearofbirth']) : 0;
    $foundUser['about'] = isset($_POST['about']) ? htmlspecialchars(mb_substr(trim($_POST['about']), 0, 500)) : '';
    $foundUser['mibile'] = isset($_POST['mibile']) ? htmlspecialchars(mb_substr(trim($_POST['mibile']), 0, 40)) : '';
    $foundUser['mail'] = isset($_POST['mail']) ? htmlspecialchars(mb_substr(trim($_POST['mail']), 0, 40)) : '';
    $foundUser['mailvis'] = isset($_POST['mailvis']) ? 1 : 0;
    $foundUser['icq'] = isset($_POST['icq']) ? (int) ($_POST['icq']) : 0;
    $foundUser['skype'] = isset($_POST['skype']) ? htmlspecialchars(mb_substr(trim($_POST['skype']), 0, 40)) : '';
    $foundUser['jabber'] = isset($_POST['jabber']) ? htmlspecialchars(mb_substr(trim($_POST['jabber']), 0, 40)) : '';
    $foundUser['www'] = isset($_POST['www']) ? htmlspecialchars(mb_substr(trim($_POST['www']), 0, 40)) : '';
    // Данные юзера (для Администраторов)
    $foundUser['name'] = isset($_POST['name']) ? htmlspecialchars(mb_substr(trim($_POST['name']), 0, 20)) : $foundUser['name'];
    $foundUser['status'] = isset($_POST['status']) ? htmlspecialchars(mb_substr(trim($_POST['status']), 0, 50)) : '';
    $foundUser['karma_off'] = isset($_POST['karma_off']) ? 1 : 0;
    $foundUser['sex'] = isset($_POST['sex']) && $_POST['sex'] === 'm' ? 'm' : 'zh';
    $foundUser['rights'] = isset($_POST['rights']) ? abs((int) ($_POST['rights'])) : $foundUser['rights'];

    // Проводим необходимые проверки
    if ($foundUser['rights'] > $user->rights || $foundUser['rights'] > 9 || $foundUser['rights'] < 0) {
        $foundUser['rights'] = 0;
    }

    if ($user->rights >= 7) {
        if (mb_strlen($foundUser['name']) < 2 || mb_strlen($foundUser['name']) > 20) {
            $error[] = __('Min. nick length 2, max. 20 characters');
        }

        $lat_nick = $tools->rusLat($foundUser['name']);

        if (preg_match("/[^0-9a-z\-\@\*\(\)\?\!\~\_\=\[\]]+/", $lat_nick)) {
            $error[] = __('Nick contains invalid characters');
        }
    }
    if ($foundUser['dayb'] || $foundUser['monthb'] || $foundUser['yearofbirth']) {
        if ($foundUser['dayb'] < 1 || $foundUser['dayb'] > 31 || $foundUser['monthb'] < 1 || $foundUser['monthb'] > 12) {
            $error[] = __('Invalid format date of birth');
        }
    }

    if ($foundUser['icq'] && ($foundUser['icq'] < 10000 || $foundUser['icq'] > 999999999)) {
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
                $foundUser['imname'],
                $foundUser['live'],
                $foundUser['dayb'],
                $foundUser['monthb'],
                $foundUser['yearofbirth'],
                $foundUser['about'],
                $foundUser['mibile'],
                $foundUser['mail'],
                $foundUser['mailvis'],
                $foundUser['icq'],
                $foundUser['skype'],
                $foundUser['jabber'],
                $foundUser['www'],
                $foundUser['id'],
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
                    $foundUser['name'],
                    $foundUser['status'],
                    $foundUser['karma_off'],
                    $foundUser['sex'],
                    $foundUser['rights'],
                    $foundUser['id'],
                ]
            );
        }
        $_SESSION['success_message'] = __('Data saved');
    } else {
        $_SESSION['edit_errors'] = $error;
    }

    header('Location: ?act=edit&user=' . $foundUser['id']);
    exit;
}

$data['form_action'] = '?act=edit&amp;user=' . $foundUser['id'];
$foundUser['about'] = htmlspecialchars($foundUser['about']);

if (! empty($_SESSION['edit_errors'])) {
    $data['errors'] = $_SESSION['edit_errors'];
    unset($_SESSION['edit_errors']);
}

if (! empty($_SESSION['success_message'])) {
    $data['success_message'] = $_SESSION['success_message'];
    unset($_SESSION['success_message']);
}

$data['user'] = $foundUser;

$avatar = UPLOAD_PATH . 'users/avatar/' . $foundUser['id'] . '.png';
if (file_exists($avatar)) {
    $data['user']['avatar_file'] = $avatar;
    $data['delete_avatar_url'] = '?act=edit&amp;user=' . $foundUser['id'] . '&amp;delavatar';
}

if (file_exists(UPLOAD_PATH . 'users/photo/' . $foundUser['id'] . '_small.jpg')) {
    $data['user']['photo'] = '/upload/users/photo/' . $foundUser['id'] . '.jpg';
    $data['user']['photo_preview'] = '/upload/users/photo/' . $foundUser['id'] . '_small.jpg';
}

echo $view->render(
    'profile::edit',
    [
        'title'      => $title,
        'page_title' => $title,
        'data'       => $data,
    ]
);
