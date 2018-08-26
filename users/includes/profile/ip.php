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
$textl = htmlspecialchars($user['name']) . ': ' . $lng['ip_history'];
require('../incfiles/head.php');

/*
-----------------------------------------------------------------
Проверяем права доступа
-----------------------------------------------------------------
*/
if (!$rights && $user_id != $user['id']) {
    echo functions::display_error($lng['access_forbidden']);
    require('../incfiles/end.php');
    exit;
}

/*
-----------------------------------------------------------------
История IP адресов
-----------------------------------------------------------------
*/
echo '<div class="phdr"><a href="profile.php?user=' . $user['id'] . '"><b>' . $lng['profile'] . '</b></a> | ' . $lng['ip_history'] . '</div>';
echo '<div class="user"><p>';
$arg = array (
    'lastvisit' => 1,
    'header' => '<b>ID:' . $user['id'] . '</b>'
);
echo functions::display_user($user, $arg);
echo '</p></div>';
$total = $db->query("SELECT COUNT(*) FROM `cms_users_iphistory` WHERE `user_id` = '" . $user['id'] . "'")->fetchColumn();
if ($total) {
    $stmt = $db->query("SELECT * FROM `cms_users_iphistory` WHERE `user_id` = '" . $user['id'] . "' ORDER BY `time` DESC LIMIT $start, $kmess");
    $i = 0;
    while ($res = $stmt->fetch()) {
        echo $i % 2 ? '<div class="list2">' : '<div class="list1">';
        $link = $rights ? '<a href="' . $set['homeurl'] . '/' . $set['admp'] . '/index.php?act=search_ip&amp;mod=history&amp;ip=' . long2ip($res['ip']) . '">' . long2ip($res['ip']) . '</a>' : long2ip($res['ip']);
        echo $link . ' <span class="gray">(' . date("d.m.Y / H:i", $res['time']) . ')</span></div>';
        ++$i;
    }
} else {
    echo '<div class="menu"><p>' . $lng['list_empty'] . '</p></div>';
}
echo '<div class="phdr">' . $lng['total'] . ': ' . $total . '</div>';
if ($total > $kmess) {
    echo '<p>' . functions::display_pagination('profile.php?act=ip&amp;user=' . $user['id'] . '&amp;', $start, $total, $kmess) . '</p>';
    echo '<p><form action="profile.php?act=ip&amp;user=' . $user['id'] . '" method="post">' .
        '<input type="text" name="page" size="2"/>' .
        '<input type="submit" value="' . $lng['to_page'] . ' &gt;&gt;"/>' .
        '</form></p>';
}
?>