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

ob_start();

// Выводим список пользователей
$total = $db->query('SELECT COUNT(*) FROM `users` WHERE `preg` = 1')->fetchColumn();
echo '<div class="phdr"><a href="./"><b>' . _t('Community') . '</b></a> | ' . _t('List of users') . '</div>';

if ($total > $kmess) {
    echo '<div class="topmenu">' . $tools->displayPagination('?act=userlist&amp;', $start, $total, $kmess) . '</div>';
}

$req = $db->query("SELECT `id`, `name`, `sex`, `lastdate`, `datereg`, `status`, `rights`, `ip`, `browser`, `rights` FROM `users` WHERE `preg` = 1 ORDER BY `datereg` DESC LIMIT ${start}, ${kmess}");

while ($res = $req->fetch()) {
    echo $i % 2 ? '<div class="list2">' : '<div class="list1">';
    echo $tools->displayUser($res) . '</div>';
    ++$i;
}

echo '<div class="phdr">' . _t('Total') . ': ' . $total . '</div>';

if ($total > $kmess) {
    echo '<div class="topmenu">' . $tools->displayPagination('?act=userlist&amp;', $start, $total, $kmess) . '</div>' .
        '<p><form action="?act=userlist" method="post">' .
        '<input type="text" name="page" size="2"/>' .
        '<input type="submit" value="' . _t('To Page') . ' &gt;&gt;"/>' .
        '</form></p>';
}

echo '<p><a href="search.php">' . _t('User Search') . '</a><br />' .
    '<a href="./">' . _t('Back') . '</a></p>';

echo $view->render('system::app/old_content', [
    'title'   => _t('List of users'),
    'content' => ob_get_clean(),
]);
