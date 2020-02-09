<?php

/**
 * This file is part of JohnCMS Content Management System.
 *
 * @copyright JohnCMS Community
 * @license   https://opensource.org/licenses/GPL-3.0 GPL-3.0
 * @link      https://johncms.com JohnCMS Project
 */

declare(strict_types=1);

defined('_IN_JOHNADM') || die('Error: restricted access');

/**
 * @var PDO $db
 * @var Johncms\System\Legacy\Tools $tools
 * @var Johncms\System\Users\User $user
 */

if ($user->rights < 9) {
    exit(__('Access denied'));
}

$title = __('Delete user');
$nav_chain->add($title);

$mod = isset($_GET['mod']) ? trim($_GET['mod']) : '';

$foundUser = false;
$error = false;

if ($id && $id !== $user->id) {
    // Получаем данные юзера
    $req = $db->query('SELECT * FROM `users` WHERE `id` = ' . $id);

    if ($req->rowCount()) {
        $foundUser = $req->fetch();

        if ($foundUser['rights'] > $user->rights) {
            $error = __('You cannot delete higher administration');
        }
    } else {
        $error = __('User does not exists');
    }
} else {
    $error = __('Wrong data');
}

if (! $error) {
    // Считаем комментарии в библиотеке
    $comm_lib = (int) $db->query("SELECT COUNT(*) FROM `cms_library_comments` WHERE `user_id` = '" . $foundUser['id'] . "'")->fetchColumn();

    // Считаем комментарии к загрузкам
    $comm_dl = (int) $db->query("SELECT COUNT(*) FROM `download__comments` WHERE `user_id` = '" . $foundUser['id'] . "'")->fetchColumn();

    // Считаем посты в личных гостевых
    $comm_gb = (int) $db->query("SELECT COUNT(*) FROM `cms_users_guestbook` WHERE `user_id` = '" . $foundUser['id'] . "'")->fetchColumn();

    // Считаем комментарии в личных альбомах
    $comm_al = (int) $db->query("SELECT COUNT(*) FROM `cms_album_comments` WHERE `user_id` = '" . $foundUser['id'] . "'")->fetchColumn();
    $comm_count = $comm_lib + $comm_dl + $comm_gb + $comm_al;

    // Считаем посты в Гостевой
    $guest_count = $db->query("SELECT COUNT(*) FROM `guest` WHERE `user_id` = '" . $foundUser['id'] . "'")->fetchColumn();

    // Считаем созданные темы на Форуме
    $forumt_count = $db->query("SELECT COUNT(*) FROM `forum_topic` WHERE `user_id` = '" . $foundUser['id'] . "' AND (`deleted` != '1' OR deleted IS NULL)")->fetchColumn();

    // Считаем посты на Форуме
    $forump_count = $db->query("SELECT COUNT(*) FROM `forum_messages` WHERE `user_id` = '" . $foundUser['id'] . "' AND (`deleted` != '1' OR deleted IS NULL)")->fetchColumn();

    switch ($mod) {
        case 'del':
            // Удаляем личные данные
            $del = new Johncms\System\Users\UserClean($db);
            $del->removeAlbum($foundUser['id']);         // Удаляем личные Фотоальбомы
            $del->removeGuestbook($foundUser['id']);     // Удаляем личную Гостевую
            $del->removeMail($foundUser['id']);          // Удаляем почту
            $del->removeKarma($foundUser['id']);         // Удаляем карму

            if (isset($_POST['comments'])) {
                $del->cleanComments($foundUser['id']);   // Удаляем комментарии
            }

            if (isset($_POST['forum'])) {
                $del->cleanForum($foundUser['id']);      // Чистим Форум
            }

            $del->removeUser($foundUser['id']);          // Удаляем пользователя

            // Оптимизируем таблицы
            $db->query(
                '
                OPTIMIZE TABLE
                `cms_users_iphistory`,
                `cms_ban_users`,
                `guest`,
                `cms_album_comments`,
                `cms_users_guestbook`,
                `karma_users`,
                `cms_album_votes`,
                `cms_album_views`,
                `cms_album_downloads`,
                `cms_album_cat`,
                `cms_album_files`,
                `cms_forum_rdm`
            '
            );

            echo $view->render(
                'system::pages/result',
                [
                    'title'   => $title,
                    'type'    => 'alert-success',
                    'message' => __('User deleted'),
                ]
            );
            break;

        default:
            $data['activity'] = [
                'comm_count'   => $comm_count ?? 0,
                'forumt_count' => $forumt_count ?? 0,
                'forump_count' => $forump_count ?? 0,
            ];
            $data['user'] = $foundUser;
            $data['form_action'] = '/admin/usr_del/?mod=del&amp;id=' . $foundUser['id'];
            $data['back_url'] = '/profile/?user=' . $foundUser['id'];
            echo $view->render(
                'admin::usr_del',
                [
                    'title'      => $title,
                    'page_title' => $title,
                    'data'       => $data,
                ]
            );
    }
} else {
    echo $view->render(
        'system::pages/result',
        [
            'title'   => $title,
            'type'    => 'alert-danger',
            'message' => $error,
        ]
    );
}
