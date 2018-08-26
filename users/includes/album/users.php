<?php

/*
////////////////////////////////////////////////////////////////////////////////
// JohnCMS                Mobile Content Management System                    //
// Project site:          http://johncms.com                                  //
// Support site:          http://gazenwagen.com                               //
////////////////////////////////////////////////////////////////////////////////
// Lead Developer:        Oleg Kasyanov   (AlkatraZ)  alkatraz@gazenwagen.com //
// Development Team:      Eugene Ryabinin (john77)    john77@gazenwagen.com   //
//                        Dmitry Liseenko (FlySelf)   flyself@johncms.com     //
////////////////////////////////////////////////////////////////////////////////
*/

defined('_IN_JOHNCMS') or die('Error: restricted access');

require('../incfiles/head.php');

/*
-----------------------------------------------------------------
Список посетителей. у которых есть фотографии
-----------------------------------------------------------------
*/
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
    (!$mod ? '<b>' . $lng['all'] . '</b>' : '<a href="album.php?act=users">' . $lng['all'] . '</a>'),
    ($mod == 'boys' ? '<b>' . $lng['mans'] . '</b>' : '<a href="album.php?act=users&amp;mod=boys">' . $lng['mans'] . '</a>'),
    ($mod == 'girls' ? '<b>' . $lng['womans'] . '</b>' : '<a href="album.php?act=users&amp;mod=girls">' . $lng['womans'] . '</a>')
);
echo '<div class="phdr"><a href="album.php"><b>' . $lng['photo_albums'] . '</b></a> | ' . $lng['list'] . '</div>' .
     '<div class="topmenu">' . functions::display_menu($menu) . '</div>';
$total = $db->query("SELECT COUNT(DISTINCT `user_id`)
    FROM `cms_album_files`
    LEFT JOIN `users` ON `cms_album_files`.`user_id` = `users`.`id` $sql
")->fetchColumn();
if ($total) {
    $stmt = $db->query("SELECT `cms_album_files`.*, COUNT(`cms_album_files`.`id`) AS `count`, `users`.`id` AS `uid`, `users`.`name` AS `nick`
        FROM `cms_album_files`
        LEFT JOIN `users` ON `cms_album_files`.`user_id` = `users`.`id` $sql
        GROUP BY `cms_album_files`.`user_id` ORDER BY `users`.`name` ASC LIMIT $start, $kmess
    ");
    $i = 0;
    while ($res = $stmt->fetch()) {
        echo $i % 2 ? '<div class="list2">' : '<div class="list1">';
        echo '<a href="album.php?act=list&amp;user=' . $res['uid'] . '">' . $res['nick'] . '</a> (' . $res['count'] . ')</div>';
        ++$i;
    }
} else {
    echo '<div class="menu"><p>' . $lng['list_empty'] . '</p></div>';
}
echo '<div class="phdr">' . $lng['total'] . ': ' . $total . '</div>';
if ($total > $kmess) {
    echo '<div class="topmenu">' . functions::display_pagination('album.php?act=users' . ($mod ? '&amp;mod=' . $mod : '') . '&amp;', $start, $total, $kmess) . '</div>' .
         '<p><form action="album.php?act=users' . ($mod ? '&amp;mod=' . $mod : '') . '" method="post">' .
         '<input type="text" name="page" size="2"/>' .
         '<input type="submit" value="' . $lng['to_page'] . ' &gt;&gt;"/>' .
         '</form></p>';
}
?>