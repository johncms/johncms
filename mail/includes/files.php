<?php

/**
 * @package     JohnCMS
 * @link        http://johncms.com
 * @copyright   Copyright (C) 2008-2011 JohnCMS Community
 * @license     LICENSE.txt (see attached file)
 * @version     VERSION.txt (see attached file)
 * @author      http://johncms.com/about
 */

defined('_IN_JOHNCMS') or die('Error: restricted access');

$headmod = 'mail';
$textl = $lng['mail'] . ' | ' . $lng['files'];
require_once('../incfiles/head.php');

echo '<div class="phdr"><b>' . $lng['files'] . '</b></div>';
//Отображаем список файлов
$total = $db->query("SELECT COUNT(*) FROM `cms_mail` WHERE (`user_id`='$user_id' OR `from_id`='$user_id') AND `delete`!='$user_id' AND `file_name`!=''")->fetchColumn();
if ($total) {
    if ($total > $kmess) echo '<div class="topmenu">' . functions::display_pagination('index.php?act=files&amp;', $start, $total, $kmess) . '</div>';
    $stmt = $db->query("SELECT `cms_mail`.*, `users`.`name`
        FROM `cms_mail`
        LEFT JOIN `users` ON `cms_mail`.`user_id`=`users`.`id`
	    WHERE (`cms_mail`.`user_id`='$user_id' OR `cms_mail`.`from_id`='$user_id')
	    AND `cms_mail`.`delete`!='$user_id'
	    AND `cms_mail`.`file_name`!=''
	    ORDER BY `cms_mail`.`time` DESC
	    LIMIT " . $start . "," . $kmess);
    $i = 0;
    while ($row = $stmt->fetch()) {
        echo ++$i % 2 ? '<div class="list1">' : '<div class="list2">';
        echo '<a href="../users/profile.php?user=' . $row['user_id'] . '"><b>' . $row['name'] . '</b></a>:: <a href="index.php?act=load&amp;id=' . $row['id'] . '">' . $row['file_name'] . '</a> (' . formatsize($row['size']) . ') (' . $row['count'] . ')';
        echo '</div>';
    }
} else {
    echo '<div class="menu"><p>' . $lng['list_empty'] . '</p></div>';
}

echo '<div class="phdr">' . $lng['total'] . ': ' . $total . '</div>';
if ($total > $kmess) {
    echo '<div class="topmenu">' . functions::display_pagination('index.php?act=files&amp;', $start, $total, $kmess) . '</div>';
    echo '<p><form action="index.php" method="get">
		<input type="hidden" name="act" value="files"/>
		<input type="text" name="page" size="2"/>
		<input type="submit" value="' . $lng['to_page'] . ' &gt;&gt;"/></form></p>';
}

echo '<p><a href="../users/profile.php?act=office">' . $lng['personal'] . '</a></p>';