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
if ($user_data->id !== $user->id && ($user->rights < 7 || $user_data->rights >= $user->rights)) {
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
    $user_save_data = [];
    $user_save_data['imname'] = isset($_POST['imname']) ? htmlspecialchars(mb_substr(trim($_POST['imname']), 0, 25)) : '';
    $user_save_data['live'] = isset($_POST['live']) ? htmlspecialchars(mb_substr(trim($_POST['live']), 0, 50)) : '';
    $user_save_data['dayb'] = isset($_POST['dayb']) ? (int) ($_POST['dayb']) : 0;
    $user_save_data['monthb'] = isset($_POST['monthb']) ? (int) ($_POST['monthb']) : 0;
    $user_save_data['yearofbirth'] = isset($_POST['yearofbirth']) ? (int) ($_POST['yearofbirth']) : 0;
    $user_save_data['about'] = isset($_POST['about']) ? htmlspecialchars(mb_substr(trim($_POST['about']), 0, 500)) : '';
    $user_save_data['mibile'] = isset($_POST['mibile']) ? htmlspecialchars(mb_substr(trim($_POST['mibile']), 0, 40)) : '';
    $user_save_data['mail'] = isset($_POST['mail']) ? htmlspecialchars(mb_substr(trim($_POST['mail']), 0, 40)) : '';
    $user_save_data['mailvis'] = isset($_POST['mailvis']);
    $user_save_data['skype'] = isset($_POST['skype']) ? htmlspecialchars(mb_substr(trim($_POST['skype']), 0, 40)) : '';
    $user_save_data['jabber'] = isset($_POST['jabber']) ? htmlspecialchars(mb_substr(trim($_POST['jabber']), 0, 40)) : '';
    $user_save_data['www'] = isset($_POST['www']) ? htmlspecialchars(mb_substr(trim($_POST['www']), 0, 40)) : '';
    // Данные юзера (для Администраторов)
    $user_save_data['name'] = isset($_POST['name']) ? htmlspecialchars(mb_substr(trim($_POST['name']), 0, 20)) : $user_data->name;
    $user_save_data['status'] = isset($_POST['status']) ? htmlspecialchars(mb_substr(trim($_POST['status']), 0, 50)) : '';
    $user_save_data['karma_off'] = isset($_POST['karma_off']);
    $user_save_data['sex'] = isset($_POST['sex']) && $_POST['sex'] === 'm' ? 'm' : 'zh';
    $user_save_data['rights'] = isset($_POST['rights']) ? abs((int) ($_POST['rights'])) : $user_data->rights;

    // Проводим необходимые проверки
    if ($user_save_data['rights'] > $user->rights || $user_save_data['rights'] > 9 || $user_save_data['rights'] < 0) {
        $user_save_data['rights'] = 0;
    }

    if ($user->rights >= 7) {
        if (mb_strlen($user_save_data['name']) < 2 || mb_strlen($user_save_data['name']) > 20) {
            $error[] = __('Min. nick length 2, max. 20 characters');
        }

        $lat_nick = $tools->rusLat($user_save_data['name']);

        if (preg_match("/[^0-9a-z\-\@\*\(\)\?\!\~\_\=\[\]]+/", $lat_nick)) {
            $error[] = __('Nick contains invalid characters');
        }
    }
    if ($user_save_data['dayb'] || $user_save_data['monthb'] || $user_save_data['yearofbirth']) {
        if ($user_save_data['dayb'] < 1 || $user_save_data['dayb'] > 31 || $user_save_data['monthb'] < 1 || $user_save_data['monthb'] > 12) {
            $error[] = __('Invalid format date of birth');
        }
    }

    if (! $error) {
        // Обновляем пользовательские данные
        if ($user->rights < 7) {
            unset($user_save_data['name'], $user_save_data['status'], $user_save_data['karma_off'], $user_save_data['sex'], $user_save_data['rights']);
        }
        $user_data->update($user_save_data);

        $_SESSION['success_message'] = __('Data saved');
    } else {
        $_SESSION['edit_errors'] = $error;
    }

    header('Location: ?act=edit&user=' . $user_data->id);
    exit;
}

$data['form_action'] = '?act=edit&amp;user=' . $user_data->id;
$user_data->about = htmlspecialchars($user_data->about);

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
