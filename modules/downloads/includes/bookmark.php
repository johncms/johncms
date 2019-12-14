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

/**
 * @var PDO                        $db
 * @var Johncms\Api\ToolsInterface $tools
 * @var Johncms\System\Users\User  $user
 */

$textl = _t('Favorites');
require 'classes/download.php';

if (! $user->isValid()) {
    echo _t('For registered users only');
    echo $view->render('system::app/old_content', ['title' => $textl ?? '', 'content' => ob_get_clean()]);
    exit;
}

echo '<div class="phdr"><a href="?"><b>' . _t('Downloads') . '</b></a> | ' . $textl . '</div>';
$total = $db->query('SELECT COUNT(*) FROM `download__bookmark` WHERE `user_id` = ' . $user->id)->fetchColumn();

// Навигация
if ($total > $user->config->kmess) {
    echo '<div class="topmenu">' . $tools->displayPagination('?act=bookmark&amp;', $start, $total, $user->config->kmess) . '</div>';
}

// Список закладок
if ($total) {
    $req_down = $db->query('SELECT `download__files`.*, `download__bookmark`.`id` AS `bid`
    FROM `download__files` LEFT JOIN `download__bookmark` ON `download__files`.`id` = `download__bookmark`.`file_id`
    WHERE `download__bookmark`.`user_id`=' . $user->id . " ORDER BY `download__files`.`time` DESC LIMIT ${start}, " . $user->config->kmess);
    $i = 0;

    while ($res_down = $req_down->fetch()) {
        echo(($i++ % 2) ? '<div class="list2">' : '<div class="list1">') . Download::displayFile($res_down) . '</div>';
    }
} else {
    echo '<div class="menu"><p>' . _t('The list is empty') . '</p></div>';
}

echo '<div class="phdr">' . _t('Total') . ': ' . $total . '</div>';

// Навигация
if ($total > $user->config->kmess) {
    echo '<div class="topmenu">' . $tools->displayPagination('?act=bookmark&amp;', $start, $total, $user->config->kmess) . '</div>' .
        '<p><form action="?" method="get">' .
        '<input type="hidden" value="bookmark" name="act" />' .
        '<input type="text" name="page" size="2"/><input type="submit" value="' . _t('To Page') . ' &gt;&gt;"/></form></p>';
}

echo '<p><a href="?">' . _t('Downloads') . '</a></p>';
echo $view->render('system::app/old_content', ['title' => $textl ?? '', 'content' => ob_get_clean()]);
