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

$headmod = 'mail';
$textl = _t('Mail') . ' | ' . _t('Files');
require_once('../system/head.php');

/** @var Psr\Container\ContainerInterface $container */
$container = App::getContainer();

/** @var PDO $db */
$db = $container->get(PDO::class);

/** @var Johncms\Api\UserInterface $systemUser */
$systemUser = $container->get(Johncms\Api\UserInterface::class);

/** @var Johncms\Api\ToolsInterface $tools */
$tools = $container->get(Johncms\Api\ToolsInterface::class);

echo '<div class="phdr"><b>' . _t('Files') . '</b></div>';

//Отображаем список файлов
$total = $db->query("SELECT COUNT(*) FROM `cms_mail` WHERE (`user_id`='" . $systemUser->id . "' OR `from_id`='" . $systemUser->id . "') AND `delete`!='" . $systemUser->id . "' AND `file_name`!=''")->fetchColumn();

if ($total) {
    if ($total > $kmess) {
        echo '<div class="topmenu">' . $tools->displayPagination('index.php?act=files&amp;', $start, $total, $kmess) . '</div>';
    }

    $req = $db->query("SELECT `cms_mail`.*, `users`.`name`
        FROM `cms_mail`
        LEFT JOIN `users` ON `cms_mail`.`user_id`=`users`.`id`
	    WHERE (`cms_mail`.`user_id`='" . $systemUser->id . "' OR `cms_mail`.`from_id`='" . $systemUser->id . "')
	    AND `cms_mail`.`delete`!='" . $systemUser->id . "'
	    AND `cms_mail`.`file_name`!=''
	    ORDER BY `cms_mail`.`time` DESC
	    LIMIT " . $start . "," . $kmess);

    for ($i = 0; ($row = $req->fetch()) !== false; ++$i) {
        echo $i % 2 ? '<div class="list1">' : '<div class="list2">';
        echo '<a href="../profile/?user=' . $row['user_id'] . '"><b>' . $row['name'] . '</b></a>:: <a href="index.php?act=load&amp;id=' . $row['id'] . '">' . $row['file_name'] . '</a> (' . formatsize($row['size']) . ') (' . $row['count'] . ')';
        echo '</div>';
    }
} else {
    echo '<div class="menu"><p>' . _t('The list is empty') . '</p></div>';
}


echo '<div class="phdr">' . _t('Total') . ': ' . $total . '</div>';

if ($total > $kmess) {
    echo '<div class="topmenu">' . $tools->displayPagination('index.php?act=files&amp;', $start, $total, $kmess) . '</div>';
    echo '<p><form action="index.php" method="get">
		<input type="hidden" name="act" value="files"/>
		<input type="text" name="page" size="2"/>
		<input type="submit" value="' . _t('To Page') . ' &gt;&gt;"/></form></p>';
}

echo '<p><a href="../profile/?act=office">' . _t('Personal') . '</a></p>';
