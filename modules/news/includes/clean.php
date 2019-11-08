<?php

declare(strict_types=1);

/*
 * This file is part of JohnCMS Content Management System.
 *
 * @copyright JohnCMS Community
 * @license   https://opensource.org/licenses/GPL-3.0 GPL-3.0
 * @link      https://johncms.com JohnCMS Project
 */

defined('_IN_JOHNCMS') || die('Error: restricted access');

// Чистка новостей
if ($user->rights >= 7) {
    echo '<div class="phdr"><a href="./"><b>' . _t('News') . '</b></a> | ' . _t('Clear') . '</div>';

    if (isset($_POST['submit'])) {
        $cl = isset($_POST['cl']) ? (int) ($_POST['cl']) : '';

        switch ($cl) {
            case '1':
                // Чистим новости, старше 1 недели
                $db->query('DELETE FROM `news` WHERE `time` <= ' . (time() - 604800));
                $db->query('OPTIMIZE TABLE `news`');

                echo '<p>' . _t('Delete all news older than 1 week') . '</p><p><a href="./">' . _t('Back to news') . '</a></p>';
                break;

            case '2':
                // Проводим полную очистку
                $db->query('TRUNCATE TABLE `news`');
                echo '<p>' . _t('Delete all news') . '</p><p><a href="./">' . _t('Back to news') . '</a></p>';
                break;
            default:
                // Чистим сообщения, старше 1 месяца
                $db->query('DELETE FROM `news` WHERE `time` <= ' . (time() - 2592000));
                $db->query('OPTIMIZE TABLE `news`;');

                echo '<p>' . _t('Delete all news older than 1 month') . '</p><p><a href="./">' . _t('Back to news') . '</a></p>';
        }
    } else {
        echo '<div class="menu"><form id="clean" method="post" action="?do=clean">' .
            '<p><h3>' . _t('Clearing parameters') . '</h3>' .
            '<input type="radio" name="cl" value="0" checked="checked" />' . _t('Older than 1 month') . '<br />' .
            '<input type="radio" name="cl" value="1" />' . _t('Older than 1 week') . '<br />' .
            '<input type="radio" name="cl" value="2" />' . _t('Clear all') . '</p>' .
            '<p><input type="submit" name="submit" value="' . _t('Clear') . '" /></p>' .
            '</form></div>' .
            '<div class="phdr"><a href="./">' . _t('Cancel') . '</a></div>';
    }
} else {
    header('location: ./');
}
