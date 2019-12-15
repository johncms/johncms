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
 * @var PDO $db
 * @var Johncms\System\Utility\Tools $tools
 * @var Johncms\System\Users\User $user
 */

$textl = _t('User Files');

require 'classes/download.php';

if (($foundUser = $tools->getUser($id)) === false) {
    echo _t('User does not exists');
    echo $view->render('system::app/old_content', ['title' => $textl ?? '', 'content' => ob_get_clean()]);
    exit;
}

echo '<div class="phdr"><a href="/profile?user=' . $id . '">' . _t('Profile') . '</a></div>' .
    '<div class="user"><p>' . $tools->displayUser($foundUser, ['iphide' => 0]) . '</p></div>' .
    '<div class="phdr"><b>' . _t('User Files') . '</b></div>';

$total = $db->query("SELECT COUNT(*) FROM `download__files` WHERE `type` = '2'  AND `user_id` = " . $id)->fetchColumn();

// Навигация
if ($total > $user->config->kmess) {
    echo '<div class="topmenu">' . $tools->displayPagination('?act=user_files&amp;id=' . $id . '&amp;', $start, $total, $user->config->kmess) . '</div>';
}

// Список файлов
$i = 0;

if ($total) {
    $req_down = $db->query("SELECT * FROM `download__files` WHERE `type` = '2'  AND `user_id` = " . $id . " ORDER BY `time` DESC LIMIT ${start}, " . $user->config->kmess);

    while ($res_down = $req_down->fetch()) {
        echo (($i++ % 2) ? '<div class="list2">' : '<div class="list1">') . Download::displayFile($res_down) . '</div>';
    }
} else {
    echo '<div class="rmenu"><p>' . _t('The list is empty') . '</p></div>';
}

echo '<div class="phdr">' . _t('Total') . ': ' . $total . '</div>';

// Навигация
if ($total > $user->config->kmess) {
    echo '<div class="topmenu">' . $tools->displayPagination('?act=user_files&amp;id=' . $id . '&amp;', $start, $total, $user->config->kmess) . '</div>' .
        '<p><form action="?" method="get">' .
        '<input type="hidden" name="USER" value="' . $id . '"/>' .
        '<input type="hidden" value="user_files" name="act" />' .
        '<input type="text" name="page" size="2"/><input type="submit" value="' . _t('To Page') . ' &gt;&gt;"/></form></p>';
}

echo '<p><a href="?">' . _t('Downloads') . '</a></p>';
echo $view->render('system::app/old_content', ['title' => $textl ?? '', 'content' => ob_get_clean()]);
