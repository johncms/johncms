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
if ($rights < 7) {
    header('Location: ' . $set['homeurl'] . '/?err'); exit;
}

echo '<div class="phdr"><a href="index.php"><b>' . $lng['admin_panel'] . '</b></a> | ' . $lng['users_clean'] . '</div>';

switch ($mod) {
    case 1:
        // Получаем список ID "мертвых" профилей
        $stmt = $db->query("SELECT `id`
            FROM `users`
            WHERE `datereg` < '" . (time() - 2592000 * 6) . "'
            AND `lastdate` < '" . (time() - 2592000 * 5) . "'
            AND `postforum` = '0'
            AND `postguest` < '10'
            AND `komm` < '10'
        ");

        if ($stmt->rowCount()) {
            $del = new CleanUser;

            // Удаляем всю информацию
            while ($res = $stmt->fetch()) {
                $del->removeAlbum($res['id']);      // Удаляем личные Фотоальбомы
                $del->removeGuestbook($res['id']);  // Удаляем личную Гостевую
                $del->removeMail($res['id']);       // Удаляем почту
                $del->removeKarma($res['id']);      // Удаляем карму
                $del->cleanComments($res['id']);    // Удаляем комментарии
                $del->removeUser($res['id']);       // Удаляем пользователя
                $db->exec("DELETE FROM `cms_forum_rdm` WHERE `user_id` = '" . $res['id'] . "'");
            }

            $db->query("OPTIMIZE TABLE
                `users`,
                `cms_album_cat`,
                `cms_album_files`,
                `cms_album_comments`,
                `cms_album_downloads`,
                `cms_album_views`,
                `cms_album_votes`,
                `cms_mail`,
                `cms_contact`,
                `cms_forum_rdm`
            ");
        }

        echo '<div class="rmenu"><p>' . $lng['dead_profiles_deleted'] . '</p><p><a href="index.php">' . $lng['continue'] . '</a></p></div>';
        break;

    default:
        $total = $db->query("SELECT COUNT(*) FROM `users`
            WHERE `datereg` < '" . (time() - 2592000 * 6) . "'
            AND `lastdate` < '" . (time() - 2592000 * 5) . "'
            AND `postforum` = '0'
            AND `postguest` < '10'
            AND `komm` < '10'")->fetchColumn();
        echo '<div class="menu">' .
            '<form action="index.php?act=usr_clean&amp;mod=1" method="post">' .
            '<p><h3>' . $lng['dead_profiles'] . '</h3>' . $lng['dead_profiles_desc'] . '</p>' .
            '<p>' . $lng['total'] . ': <b>' . $total . '</b></p>' .
            '<p><input type="submit" name="submit" value="' . $lng['delete'] . '"/></p></form></div>' .
            '<div class="phdr"><a href="index.php">' . $lng['back'] . '</a></div>';
}
