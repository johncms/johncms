<?php

/**
 * @package     JohnCMS
 * @link        http://johncms.com
 * @copyright   Copyright (C) 2008-2011 JohnCMS Community
 * @license     LICENSE.txt (see attached file)
 * @version     VERSION.txt (see attached file)
 * @author      http://johncms.com/about
 */

defined('_IN_JOHNADM') or die('Error: restricted access');

// Проверяем права доступа
if ($rights < 9) {
    header('Location: ' . $set['homeurl'] . '/?err'); exit;
}

$user = false;
$error = false;
if ($id && $id != $user_id) {
    // Получаем данные юзера
    $stmt = $db->query("SELECT * FROM `users` WHERE `id` = '$id' LIMIT 1");
    if ($stmt->rowCount()) {
        $user = $stmt->fetch();
        if ($user['rights'] > $datauser['rights']) {
            $error = $lng['error_usrdel_rights'];
        }
    } else {
        $error = $lng['error_user_not_exist'];
    }
} else {
    $error = $lng['error_wrong_data'];
}
if (!$error) {
    // Считаем комментарии в галерее
    $comm_gal = $db->query("SELECT COUNT(*) FROM `gallery` WHERE `avtor` = '" . $user['name'] . "' AND `type` = 'km'")->fetchColumn();
    // Считаем комментарии в библиотеке
    $comm_lib = $db->query("SELECT COUNT(*) FROM `cms_library_comments` WHERE `user_id` = '" . $user['id'] . "'")->fetchColumn();
    // Считаем комментарии к загрузкам
    $comm_dl = $db->query("SELECT COUNT(*) FROM `download` WHERE `avtor` = '" . $user['name'] . "' AND `type` = 'komm'")->fetchColumn();
    // Считаем посты в личных гостевых
    $comm_gb = $db->query("SELECT COUNT(*) FROM `cms_users_guestbook` WHERE `user_id` = '" . $user['id'] . "'")->fetchColumn();
    // Считаем комментарии в личных альбомах
    $comm_al = $db->query("SELECT COUNT(*) FROM `cms_album_comments` WHERE `user_id` = '" . $user['id'] . "'")->fetchColumn();
    $comm_count = $comm_gal + $comm_lib + $comm_dl + $comm_gb + $comm_al;
    // Считаем посты в Гостевой
    $guest_count = $db->query("SELECT COUNT(*) FROM `guest` WHERE `user_id` = '" . $user['id'] . "'")->fetchColumn();
    // Считаем созданные темы на Форуме
    $forumt_count = $db->query("SELECT COUNT(*) FROM `forum` WHERE `user_id` = '" . $user['id'] . "' AND `type` = 't' AND `close` != '1'")->fetchColumn();
    // Считаем посты на Форуме
    $forump_count = $db->query("SELECT COUNT(*) FROM `forum` WHERE `user_id` = '" . $user['id'] . "' AND `type` = 'm'  AND `close` != '1'")->fetchColumn();
    echo '<div class="phdr"><a href="index.php"><b>' . $lng['admin_panel'] . '</b></a> | ' . $lng['user_del'] . '</div>';
    // Выводим краткие данные
    echo '<div class="user"><p>' . functions::display_user($user, array(
            'lastvisit' => 1,
            'iphist'    => 1
        )) . '</p></div>';

    switch ($mod) {

        case 'del':
            /*
            -----------------------------------------------------------------
            Удаляем личные данные
            -----------------------------------------------------------------
            */
            $del = new CleanUser;
            $del->removeAlbum($user['id']);         // Удаляем личные Фотоальбомы
            $del->removeGuestbook($user['id']);     // Удаляем личную Гостевую
            $del->removeMail($user['id']);          // Удаляем почту
            $del->removeKarma($user['id']);         // Удаляем карму

            if (isset($_POST['comments'])) {
                $del->cleanComments($user['id']);   // Удаляем комментарии
            }

            if (isset($_POST['forum'])) {
                $del->cleanForum($user['id']);      // Чистим Форум
            }

            $del->removeUser($user['id']);          // Удаляем пользователя

            // Оптимизируем таблицы
            $db->query("OPTIMIZE TABLE
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
            ");

            echo '<div class="rmenu"><p><h3>' . $lng['user_deleted'] . '</h3></p></div>';
            break;

        default:
            ////////////////////////////////////////////////////////////
            // Форма параметров удаления                              //
            ////////////////////////////////////////////////////////////
            echo '<form action="index.php?act=usr_del&amp;mod=del&amp;id=' . $user['id'] . '" method="post"><div class="menu"><p><h3>' . $lng['user_del_activity'] . '</h3>';
            if ($comm_count) {
                echo '<div><input type="checkbox" value="1" name="comments" checked="checked" />&#160;' . $lng['comments'] . ' <span class="red">(' . $comm_count . ')</span></div>';
            }
            if ($forumt_count || $forump_count) {
                echo '<div><input type="checkbox" value="1" name="forum" checked="checked" />&#160;' . $lng['forum'] . ' <span class="red">(' . $forumt_count . '&nbsp;/&nbsp;' . $forump_count . ')</span></div>';
                echo '<small><span class="gray">' . $lng['user_del_forumnote'] . '</span></small>';
            }
            echo '</p></div><div class="rmenu"><p>' . $lng['user_del_confirm'];
            echo '</p><p><input type="submit" value="' . $lng['delete'] . '" name="submit" />';
            echo '</p></div></form>';
            echo '<div class="phdr"><a href="../users/profile.php?user=' . $user['id'] . '">' . $lng['to_form'] . '</a></div>';
    }
} else {
    echo functions::display_error($error);
}
echo '<p><a href="index.php?act=users">' . $lng['users_list'] . '</a><br /><a href="index.php">' . $lng['admin_panel'] . '</a></p>';
