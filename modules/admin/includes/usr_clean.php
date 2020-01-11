<?php

declare(strict_types=1);

/*
 * This file is part of JohnCMS Content Management System.
 *
 * @copyright JohnCMS Community
 * @license   https://opensource.org/licenses/GPL-3.0 GPL-3.0
 * @link      https://johncms.com JohnCMS Project
 */

defined('_IN_JOHNADM') || die('Error: restricted access');
ob_start(); // Перехват вывода скриптов без шаблона

/**
 * @var PDO $db
 */

$mod = isset($_GET['mod']) ? trim($_GET['mod']) : '';

echo '<div class="phdr"><a href="./"><b>' . __('Admin Panel') . '</b></a> | ' . __('Database cleanup') . '</div>';

switch ($mod) {
    case 1:
        // Получаем список ID "мертвых" профилей
        $req = $db->query(
            "SELECT `id`
            FROM `users`
            WHERE `datereg` < '" . (time() - 2592000 * 6) . "'
            AND `lastdate` < '" . (time() - 2592000 * 5) . "'
            AND `postforum` = '0'
            AND `postguest` < '10'
            AND `komm` < '10'
        "
        );

        if ($req->rowCount()) {
            $del = new Johncms\System\Users\UserClean($db);

            // Удаляем всю информацию
            while ($res = $req->fetch()) {
                $del->removeAlbum($res['id']);      // Удаляем личные Фотоальбомы
                $del->removeGuestbook($res['id']);  // Удаляем личную Гостевую
                $del->removeMail($res['id']);       // Удаляем почту
                $del->removeKarma($res['id']);      // Удаляем карму
                $del->cleanComments($res['id']);    // Удаляем комментарии
                $del->removeUser($res['id']);       // Удаляем пользователя
                $db->exec('DELETE FROM `cms_forum_rdm` WHERE `user_id` = ' . $res['id']);
            }

            $db->exec(
                '
                OPTIMIZE TABLE
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
            '
            );
        }

        echo '<div class="rmenu"><p>' . __('Inactive profiles deleted') . '</p><p><a href="./">' . __('Continue') . '</a></p></div>';
        break;

    default:
        $total = $db->query(
            "SELECT COUNT(*) FROM `users`
            WHERE `datereg` < '" . (time() - 2592000 * 6) . "'
            AND `lastdate` < '" . (time() - 2592000 * 5) . "'
            AND `postforum` = '0'
            AND `postguest` < '10'
            AND `komm` < '10'"
        )->fetchColumn();
        echo '<div class="menu">' .
            '<form action="?act=usr_clean&amp;mod=1" method="post">' .
            '<p><h3>' . __('Inactive profiles') . '</h3>'
            . __('This category includes profiles, recorded more than 6 months ago, with the date of last visit for more than 5 months ago and with zero activity.<br>Can safely remove them.') . '</p>' .
            '<p>' . __('Total') . ': <b>' . $total . '</b></p>' .
            '<p><input type="submit" name="submit" value="' . __('Delete') . '"/></p></form></div>' .
            '<div class="phdr"><a href="./">' . __('Back') . '</a></div>';
}

echo $view->render(
    'system::app/old_content',
    [
        'title'   => __('Admin Panel'),
        'content' => ob_get_clean(),
    ]
);
