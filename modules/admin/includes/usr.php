<?php

declare(strict_types=1);

/*
 * This file is part of JohnCMS Content Management System.
 *
 * @copyright JohnCMS Community
 * @license   https://opensource.org/licenses/GPL-3.0 GPL-3.0
 * @link      https://johncms.com JohnCMS Project
 */

defined('_IN_JOHNADM') || die('Error: restricted access');
ob_start(); // Перехват вывода скриптов без шаблона

/**
 * @var PDO                        $db
 * @var Johncms\Api\ToolsInterface $tools
 */

echo '<div class="phdr"><a href="./"><b>' . _t('Admin Panel') . '</b></a> | ' . _t('List of Users') . '</div>';
$sort = isset($_GET['sort']) ? trim($_GET['sort']) : '';
echo '<div class="topmenu"><span class="gray">' . _t('Sort') . ':</span> ';

switch ($sort) {
    case 'nick':
        $sort = 'nick';
        echo '<a href="?act=usr&amp;sort=id">ID</a> | ' . _t('Nickname') . ' | <a href="?act=usr&amp;sort=ip">IP</a></div>';
        $order = '`name` ASC';
        break;

    case 'ip':
        $sort = 'ip';
        echo '<a href="?act=usr&amp;sort=id">ID</a> | <a href="?act=usr&amp;sort=nick">' . _t('Nickname') . '</a> | IP</div>';
        $order = '`ip` ASC';
        break;

    default:
        $sort = 'id';
        echo 'ID | <a href="?act=usr&amp;sort=nick">' . _t('Nickname') . '</a> | <a href="?act=usr&amp;sort=ip">IP</a></div>';
        $order = '`id` ASC';
}

$total = $db->query('SELECT COUNT(*) FROM `users`')->fetchColumn();
$req = $db->query("SELECT * FROM `users` WHERE `preg` = 1 ORDER BY ${order} LIMIT " . $start . ', ' . $user->config->kmess);
$i = 0;

while ($res = $req->fetch()) {
    $link = '<a href="../profile/?act=edit&amp;user=' . $res['id'] . '">' . _t('Edit') . '</a> | <a href="?act=usr_del&amp;id=' . $res['id'] . '">' . _t('Delete') . '</a> | ';
    $link .= '<a href="../profile/?act=ban&amp;mod=do&amp;user=' . $res['id'] . '">' . _t('Ban') . '</a>';
    echo ($i % 2) ? '<div class="list2">' : '<div class="list1">';
    echo $tools->displayUser($res, ['header' => ('<b>ID:' . $res['id'] . '</b>'), 'sub' => $link]);
    echo '</div>';
    ++$i;
}

echo '<div class="phdr">' . _t('Total') . ': ' . $total . '</div>';

if ($total > $user->config->kmess) {
    echo '<div class="topmenu">' . $tools->displayPagination('?act=usr&amp;sort=' . $sort . '&amp;', $start, $total, $user->config->kmess) . '</div>';
    echo '<p><form action="?act=usr&amp;sort=' . $sort . '" method="post"><input type="text" name="page" size="2"/><input type="submit" value="' . _t('To Page') . ' &gt;&gt;"/></form></p>';
}

echo '<p><a href="./">' . _t('Admin Panel') . '</a></p>';

echo $view->render('system::app/old_content', [
    'title'   => _t('Admin Panel'),
    'content' => ob_get_clean(),
]);
