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

defined('_IN_JOHNADM') or die('Error: restricted access');

echo '<div class="phdr"><a href="index.php"><b>' . $lng['admin_panel'] . '</b></a> | ' . $lng['users_list'] . '</div>';
$sort = isset($_GET['sort']) ? trim($_GET['sort']) : '';
echo '<div class="topmenu"><span class="gray">' . $lng['sorting'] . ':</span> ';
switch ($sort) {
    case 'nick':
        $sort = 'nick';
        echo '<a href="index.php?act=users&amp;sort=id">ID</a> | ' . $lng['nick'] . ' | <a href="index.php?act=users&amp;sort=ip">IP</a></div>';
        $order = '`name` ASC';
        break;

    case 'ip':
        $sort = 'ip';
        echo '<a href="index.php?act=users&amp;sort=id">ID</a> | <a href="index.php?act=users&amp;sort=nick">' . $lng['nick'] . '</a> | IP</div>';
        $order = '`ip` ASC';
        break;
        default :
    $sort = 'id';
        echo 'ID | <a href="index.php?act=users&amp;sort=nick">' . $lng['nick'] . '</a> | <a href="index.php?act=users&amp;sort=ip">IP</a></div>';
        $order = '`id` ASC';
}
$req = mysql_query("SELECT COUNT(*) FROM `users`");
$total = mysql_result($req, 0);
$req = mysql_query("SELECT * FROM `users` WHERE `preg` = 1 ORDER BY $order LIMIT $start,$kmess");
while ($res = mysql_fetch_array($req)) {
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
    echo '<p>' . functions::display_pagination('index.php?act=users&amp;sort=' . $sort . '&amp;', $start, $total, $kmess) . '</p>';
    echo '<p><form action="index.php?act=users&amp;sort=' . $sort . '" method="post"><input type="text" name="page" size="2"/><input type="submit" value="' . $lng['to_page'] . ' &gt;&gt;"/></form></p>';
}
echo '<p><a href="index.php?act=search_user">' . $lng['search_user'] . '</a><br /><a href="index.php">' . $lng['admin_panel'] . '</a></p>';

?>