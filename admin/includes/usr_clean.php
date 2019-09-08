<?php
/*
 * JohnCMS NEXT Mobile Content Management System (http://johncms.com)
 *
 * For copyright and license information, please see the LICENSE.md
 * Installing the system or redistributions of files must retain the above copyright notice.
 *
 * @link        http://johncms.com JohnCMS Project
 * @copyright   Copyright (C) JohnCMS Community
 * @license     GPL-3
 */

defined('_IN_JOHNADM') or die('Error: restricted access');

/** @var Psr\Container\ContainerInterface $container */
$container = App::getContainer();

/** @var PDO $db */
$db = $container->get(PDO::class);

/** @var Johncms\Api\UserInterface $systemUser */
$systemUser = $container->get(Johncms\Api\UserInterface::class);

// Проверяем права доступа
if ($systemUser->rights < 7) {
    header('Location: http://johncms.com/?err');
    exit;
}

$mod = isset($_GET['mod']) ? trim($_GET['mod']) : '';

echo '<div class="phdr"><a href="index.php"><b>' . _t('Admin Panel') . '</b></a> | ' . _t('Database cleanup') . '</div>';

switch ($mod) {
    case 1:
        // Получаем список ID "мертвых" профилей
        $req = $db->query("SELECT `id`
            FROM `users`
            WHERE `datereg` < '" . (time() - 2592000 * 6) . "'
            AND `lastdate` < '" . (time() - 2592000 * 5) . "'
            AND `postforum` = '0'
            AND `postguest` < '10'
            AND `komm` < '10'
        ");

        if ($req->rowCount()) {
            $del = new Johncms\CleanUser;

            // Удаляем всю информацию
            while ($res = $req->fetch()) {
                $del->removeAlbum($res['id']);      // Удаляем личные Фотоальбомы
                $del->removeGuestbook($res['id']);  // Удаляем личную Гостевую
                $del->removeMail($res['id']);       // Удаляем почту
                $del->removeKarma($res['id']);      // Удаляем карму
                $del->cleanComments($res['id']);    // Удаляем комментарии
                $del->removeUser($res['id']);       // Удаляем пользователя
                $db->exec("DELETE FROM `cms_forum_rdm` WHERE `user_id` = " . $res['id']);
            }

            $db->query("
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
            ");
        }

        echo '<div class="rmenu"><p>' . _t('Inactive profiles deleted') . '</p><p><a href="index.php">' . _t('Continue') . '</a></p></div>';
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
            '<p><h3>' . _t('Inactive profiles') . '</h3>'
            . _t('This category includes profiles, recorded more than 6 months ago, with the date of last visit for more than 5 months ago and with zero activity.<br>Can safely remove them.') . '</p>' .
            '<p>' . _t('Total') . ': <b>' . $total . '</b></p>' .
            '<p><input type="submit" name="submit" value="' . _t('Delete') . '"/></p></form></div>' .
            '<div class="phdr"><a href="index.php">' . _t('Back') . '</a></div>';
}
