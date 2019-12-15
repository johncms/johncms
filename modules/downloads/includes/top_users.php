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
 */

$textl = _t('Top Users');
echo '<div class="phdr"><a href="?"><b>' . _t('Downloads') . '</b></a> | ' . $textl . '</div>';
$req = $db->query('SELECT * FROM `download__files` WHERE `user_id` > 0 GROUP BY `user_id` ORDER BY COUNT(`user_id`)');
$total = $req->rowCount();

// Навигация
if ($total > $user->config->kmess) {
    echo '<div class="topmenu">' . $tools->displayPagination('?act=top_users&amp;', $start, $total, $user->config->kmess) . '</div>';
}

// Список файлов
$i = 0;

if ($total) {
    $req_down = $db->query("SELECT *, COUNT(`user_id`) AS `count` FROM `download__files` WHERE `user_id` > 0 GROUP BY `user_id` ORDER BY `count` DESC LIMIT ${start}, " . $user->config->kmess);

    while ($res_down = $req_down->fetch()) {
        $foundUser = $db->query('SELECT * FROM `users` WHERE `id`=' . $res_down['user_id'])->fetch();
        echo (($i++ % 2) ? '<div class="list2">' : '<div class="list1">') .
            $tools->displayUser(
                $foundUser,
                [
                    'iphide' => 0,
                    'sub'    => '<a href="?act=user_files&amp;id=' . $foundUser['id'] . '">' . _t('User Files') . ':</a> ' . $res_down['count'],
                ]
            ) . '</div>';
    }
} else {
    echo '<div class="menu"><p>' . _t('The list is empty') . '</p></div>';
}

echo '<div class="phdr">' . _t('Total') . ': ' . $total . '</div>';

// Навигация
if ($total > $user->config->kmess) {
    echo '<div class="topmenu">' . $tools->displayPagination('?act=top_users&amp;', $start, $total, $user->config->kmess) . '</div>' .
        '<p><form action="?" method="get">' .
        '<input type="hidden" value="top_users" name="act" />' .
        '<input type="text" name="page" size="2"/><input type="submit" value="' . _t('To Page') . ' &gt;&gt;"/></form></p>';
}

echo '<p><a href="?">' . _t('Downloads') . '</a></p>';
echo $view->render('system::app/old_content', ['title' => $textl ?? '', 'content' => ob_get_clean()]);
