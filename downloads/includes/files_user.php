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

defined('_IN_JOHNCMS') or die('Error: restricted access');

/** @var Psr\Container\ContainerInterface $container */
$container = App::getContainer();

/** @var PDO $db */
$db = $container->get(PDO::class);

/** @var Johncms\Api\UserInterface $systemUser */
$systemUser = $container->get(Johncms\Api\UserInterface::class);

/** @var Johncms\Api\ToolsInterface $tools */
$tools = $container->get(Johncms\Api\ToolsInterface::class);

// Файлы юзера
$textl = _t('User Files');

require 'classes/download.php';
require '../system/head.php';

if (($user = $tools->getUser($id)) === false) {
    echo _t('User does not exists');
    require '../system/end.php';
    exit;
}

echo '<div class="phdr"><a href="/profile?user=' . $id . '">' . _t('Profile') . '</a></div>' .
    '<div class="user"><p>' . $tools->displayUser($user, ['iphide' => 0]) . '</p></div>' .
    '<div class="phdr"><b>' . _t('User Files') . '</b></div>';

$total = $db->query("SELECT COUNT(*) FROM `download__files` WHERE `type` = '2'  AND `user_id` = " . $id)->fetchColumn();

// Навигация
if ($total > $kmess) {
    echo '<div class="topmenu">' . $tools->displayPagination('?act=user_files&amp;id=' . $id . '&amp;', $start, $total, $kmess) . '</div>';
}

// Список файлов
$i = 0;

if ($total) {
    $req_down = $db->query("SELECT * FROM `download__files` WHERE `type` = '2'  AND `user_id` = " . $id . " ORDER BY `time` DESC LIMIT $start, $kmess");

    while ($res_down = $req_down->fetch()) {
        echo (($i++ % 2) ? '<div class="list2">' : '<div class="list1">') . Download::displayFile($res_down) . '</div>';
    }
} else {
    echo '<div class="rmenu"><p>' . _t('The list is empty') . '</p></div>';
}

echo '<div class="phdr">' . _t('Total') . ': ' . $total . '</div>';

// Навигация
if ($total > $kmess) {
    echo '<div class="topmenu">' . $tools->displayPagination('?act=user_files&amp;id=' . $id . '&amp;', $start, $total, $kmess) . '</div>' .
        '<p><form action="?" method="get">' .
        '<input type="hidden" name="USER" value="' . $id . '"/>' .
        '<input type="hidden" value="user_files" name="act" />' .
        '<input type="text" name="page" size="2"/><input type="submit" value="' . _t('To Page') . ' &gt;&gt;"/></form></p>';
}

echo '<p><a href="?">' . _t('Downloads') . '</a></p>';
require '../system/end.php';
