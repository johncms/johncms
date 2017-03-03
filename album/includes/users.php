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

$mod = isset($_GET['mod']) ? trim($_GET['mod']) : '';

require('../system/head.php');

// Список посетителей. у которых есть фотографии
switch ($mod) {
    case 'boys':
        $sql = "WHERE `users`.`sex` = 'm'";
        break;

    case 'girls':
        $sql = "WHERE `users`.`sex` = 'zh'";
        break;
    default:
        $sql = "WHERE `users`.`sex` != ''";
}

$menu = array(
    (!$mod ? '<b>' . _t('All') . '</b>' : '<a href="?act=users">' . _t('All') . '</a>'),
    ($mod == 'boys' ? '<b>' . _t('Guys') . '</b>' : '<a href="?act=users&amp;mod=boys">' . _t('Guys') . '</a>'),
    ($mod == 'girls' ? '<b>' . _t('Girls') . '</b>' : '<a href="?act=users&amp;mod=girls">' . _t('Girls') . '</a>')
);
echo '<div class="phdr"><a href="index.php"><b>' . _t('Photo Albums') . '</b></a> | ' . _t('List') . '</div>' .
     '<div class="topmenu">' . implode(' | ', $menu) . '</div>';

$total = $db->query("SELECT COUNT(DISTINCT `user_id`)
    FROM `cms_album_files`
    LEFT JOIN `users` ON `cms_album_files`.`user_id` = `users`.`id` $sql
")->fetchColumn();

if ($total) {
    $req = $db->query("SELECT `cms_album_files`.*, COUNT(`cms_album_files`.`id`) AS `count`, `users`.`id` AS `uid`, `users`.`name` AS `nick`
        FROM `cms_album_files`
        LEFT JOIN `users` ON `cms_album_files`.`user_id` = `users`.`id` $sql
        GROUP BY `cms_album_files`.`user_id` ORDER BY `users`.`name` ASC LIMIT $start, $kmess
    ");
    $i = 0;

    while ($res = $req->fetch()) {
        echo $i % 2 ? '<div class="list2">' : '<div class="list1">';
        echo '<a href="?act=list&amp;user=' . $res['uid'] . '">' . $res['nick'] . '</a> (' . $res['count'] . ')</div>';
        ++$i;
    }
} else {
    echo '<div class="menu"><p>' . _t('The list is empty') . '</p></div>';
}
echo '<div class="phdr">' . _t('Total') . ': ' . $total . '</div>';
if ($total > $kmess) {
    echo '<div class="topmenu">' . $tools->displayPagination('?act=users' . ($mod ? '&amp;mod=' . $mod : '') . '&amp;', $start, $total, $kmess) . '</div>' .
         '<p><form action="?act=users' . ($mod ? '&amp;mod=' . $mod : '') . '" method="post">' .
         '<input type="text" name="page" size="2"/>' .
         '<input type="submit" value="' . _t('To Page') . ' &gt;&gt;"/>' .
         '</form></p>';
}
