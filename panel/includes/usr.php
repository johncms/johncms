<?php

/**
* @package     JohnCMS
* @link        http://johncms.com
* @copyright   Copyright (C) 2008-2011 JohnCMS Community
* @license     LICENSE.txt (see attached file)
* @version     VERSION.txt (see attached file)
* @author      http://johncms.com/about
*/

defined('_IN_JOHNADM') or die('Error: restricted access');

echo '<div class="phdr"><a href="index.php"><b>' . $lng['admin_panel'] . '</b></a> | ' . $lng['users_list'] . '</div>';
$sort = isset($_GET['sort']) ? trim($_GET['sort']) : '';
echo '<div class="topmenu"><span class="gray">' . $lng['sorting'] . ':</span> ';
switch ($sort) {
    case 'nick':
        $sort = 'nick';
        echo '<a href="index.php?act=usr&amp;sort=id">ID</a> | ' . $lng['nick'] . ' | <a href="index.php?act=usr&amp;sort=ip">IP</a></div>';
        $order = '`name` ASC';
        break;

    case 'ip':
        $sort = 'ip';
        echo '<a href="index.php?act=usr&amp;sort=id">ID</a> | <a href="index.php?act=usr&amp;sort=nick">' . $lng['nick'] . '</a> | IP</div>';
        $order = '`ip` ASC';
        break;
        default :
    $sort = 'id';
        echo 'ID | <a href="index.php?act=usr&amp;sort=nick">' . $lng['nick'] . '</a> | <a href="index.php?act=usr&amp;sort=ip">IP</a></div>';
        $order = '`id` ASC';
}
$req = mysql_query("SELECT COUNT(*) FROM `users`");
$total = mysql_result($req, 0);
$req = mysql_query("SELECT * FROM `users` WHERE `preg` = 1 ORDER BY $order LIMIT " . $start . ", " . $kmess);
$i = 0;
while (($res = mysql_fetch_assoc($req)) !== false) {
    $link = '';
    if ($rights >= 7)
        $link .= '<a href="../users/profile.php?act=edit&amp;user=' . $res['id'] . '">' . $lng['edit'] . '</a> | <a href="index.php?act=usr_del&amp;id=' . $res['id'] . '">' . $lng['delete'] . '</a> | ';
    $link .= '<a href="../users/profile.php?act=ban&amp;mod=do&amp;user=' . $res['id'] . '">' . $lng['ban_do'] . '</a>';
    echo $i % 2 ? '<div class="list2">' : '<div class="list1">';
    echo functions::display_user($res, array('header' => ('<b>ID:' . $res['id'] . '</b>'), 'sub' => $link));
    echo '</div>';
    ++$i;
}
echo '<div class="phdr">' . $lng['total'] . ': ' . $total . '</div>';
if ($total > $kmess) {
    echo '<div class="topmenu">' . functions::display_pagination('index.php?act=usr&amp;sort=' . $sort . '&amp;', $start, $total, $kmess) . '</div>';
    echo '<p><form action="index.php?act=usr&amp;sort=' . $sort . '" method="post"><input type="text" name="page" size="2"/><input type="submit" value="' . $lng['to_page'] . ' &gt;&gt;"/></form></p>';
}
echo '<p><a href="index.php?act=search_user">' . $lng['search_user'] . '</a><br /><a href="index.php">' . $lng['admin_panel'] . '</a></p>';

?>