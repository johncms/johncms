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
 */

$mod = isset($_GET['mod']) ? trim($_GET['mod']) : '';
$title = __('Database cleanup');
$nav_chain->add($title);


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
        echo $view->render(
            'system::pages/result',
            [
                'title'         => $title,
                'type'          => 'alert-success',
                'message'       => __('Inactive profiles deleted'),
                'back_url'      => '/admin/usr_clean/',
                'back_url_name' => __('Continue'),
                'admin'         => true,
                'menu_item'     => 'usr_clean',
                'parent_menu'   => 'usr_menu',
            ]
        );
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

        $data = [
            'form_action' => '?mod=1',
            'total'       => $total,
            'back_url'    => '/admin/',
        ];

        echo $view->render(
            'admin::user_clean_confirm',
            [
                'title'      => $title,
                'page_title' => $title,
                'data'       => $data,
            ]
        );
}
