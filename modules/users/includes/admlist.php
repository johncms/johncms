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

// Выводим список администрации
echo '<div class="phdr"><a href="./"><b>' . _t('Community') . '</b></a> | ' . _t('Administration') . '</div>';
$total = $db->query('SELECT COUNT(*) FROM `users` WHERE `rights` >= 1')->fetchColumn();
$req = $db->query("SELECT `id`, `name`, `sex`, `lastdate`, `datereg`, `status`, `rights`, `ip`, `browser`, `rights` FROM `users` WHERE `rights` >= 1 ORDER BY `rights` DESC LIMIT ${start}, ${kmess}");

for ($i = 0; $res = $req->fetch(); ++$i) {
    echo $i % 2 ? '<div class="list2">' : '<div class="list1">';
    echo $tools->displayUser($res) . '</div>';
}

echo '<div class="phdr">' . _t('Total') . ': ' . $total . '</div>';

if ($total > $kmess) {
    echo '<p>' . $tools->displayPagination('?act=admlist&amp;', $start, $total, $kmess) . '</p>' .
        '<p><form action="?act=admlist" method="post">' .
        '<input type="text" name="page" size="2"/>' .
        '<input type="submit" value="' . _t('To Page') . ' &gt;&gt;"/>' .
        '</form></p>';
}

echo'<p><a href="?act=search">' . _t('User Search') . '</a><br />' .
    '<a href="./">' . _t('Back') . '</a></p>';

echo $view->render('system::app/old_content', [
    'title'   => _t('Administration'),
    'content' => ob_get_clean(),
]);
