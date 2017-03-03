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

/** @var Johncms\Api\ToolsInterface $tools */
$tools = $container->get(Johncms\Api\ToolsInterface::class);

require '../system/head.php';
require 'classes/download.php';

// Новые файлы
$textl = _t('New Files');
$sql_down = '';

if ($id) {
    $cat = $db->query("SELECT * FROM `download__category` WHERE `id` = '" . $id . "' LIMIT 1");
    $res_down_cat = $cat->fetch();

    if (!$cat->rowCount() || !is_dir($res_down_cat['dir'])) {
        echo _t('The directory does not exist') . '<a href="?">' . _t('Downloads') . '</a>';
        exit;
    }

    $title_pages = htmlspecialchars(mb_substr($res_down_cat['rus_name'], 0, 30));
    $textl = _t('New Files') . ': ' . (mb_strlen($res_down_cat['rus_name']) > 30 ? $title_pages . '...' : $title_pages);
    $sql_down = ' AND `dir` LIKE \'' . ($res_down_cat['dir']) . '%\' ';
}

echo '<div class="phdr"><a href="?"><b>' . _t('Downloads') . '</b></a> | ' . $textl . '</div>';
$total = $db->query("SELECT COUNT(*) FROM `download__files` WHERE `type` = '2'  AND `time` > $old $sql_down")->fetchColumn();

// Навигация
if ($total > $kmess) {
    echo '<div class="topmenu">' . $tools->displayPagination('?id=' . $id . '&amp;act=new_files&amp;', $start, $total, $kmess) . '</div>';
}

// Выводим список
if ($total) {
    $i = 0;
    $req_down = $db->query("SELECT * FROM `download__files` WHERE `type` = '2'  AND `time` > $old $sql_down ORDER BY `time` DESC LIMIT $start, $kmess");

    while ($res_down = $req_down->fetch()) {
        echo (($i++ % 2) ? '<div class="list2">' : '<div class="list1">') . Download::displayFile($res_down) . '</div>';
    }
} else {
    echo '<div class="rmenu"><p>' . _t('The list is empty') . '</p></div>';
}

echo '<div class="phdr">' . _t('Total') . ': ' . $total . '</div>';

// Навигация
if ($total > $kmess) {
    echo '<div class="topmenu">' . $tools->displayPagination('?id=' . $id . '&amp;act=new_files&amp;', $start, $total, $kmess) . '</div>' .
        '<p><form action="?" method="get">' .
        '<input type="hidden" name="id" value="' . $id . '"/>' .
        '<input type="hidden" value="new_files" name="act" />' .
        '<input type="text" name="page" size="2"/><input type="submit" value="' . _t('To Page') . ' &gt;&gt;"/></form></p>';
}

echo '<p><a href="?id=' . $id . '">' . _t('Downloads') . '</a></p>';
require '../system/end.php';
