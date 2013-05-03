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
$out = '';
$total = 0;

if ($mod == 'clear') {
    if (isset($_POST['clear'])) {
        $count_message = mysql_result(mysql_query("SELECT COUNT(*) FROM `cms_mail` WHERE `from_id`='$user_id' AND `sys`='1';"), 0);
        if ($count_message) {
            $req = mysql_query("SELECT `id` FROM `cms_mail` WHERE `from_id`='$user_id' AND `sys`='1' LIMIT " . $count_message);
            $mass_del = array();
            while (($row = mysql_fetch_assoc($req)) !== FALSE) {
                $mass_del[] = $row['id'];
            }
            if ($mass_del) {
                $result = implode(',', $mass_del);
                mysql_query("DELETE FROM `cms_mail` WHERE `id` IN (" . $result . ")");
            }
        }
        $out .= '<div class="gmenu">' . $lng_mail['messages_are_removed'] . '</div>';
    } else {
        $out .= '
		<div class="rmenu">' . $lng_mail['really_messages_removed'] . '</div>
		<div class="gmenu">
		<form action="index.php?act=systems&amp;mod=clear" method="post"><div>
		<input type="submit" name="clear" value="' . $lng['delete'] . '"/>
		</div></form>
		</div>';
    }
} else {
    $total = mysql_result(mysql_query("SELECT COUNT(*) FROM `cms_mail` WHERE `from_id`='$user_id' AND `sys`='1' AND `delete`!='$user_id';"), 0);
    if ($total) {
        function time_parce($var)
        {
            return functions::display_date($var[1]);
        }

        if ($total > $kmess) {
            $out .= '<div class="topmenu">' . functions::display_pagination('index.php?act=systems&amp;', $start, $total, $kmess) . '</div>';
        }
        $req = mysql_query("SELECT * FROM `cms_mail` WHERE `from_id`='$user_id' AND `sys`='1' AND `delete`!='$user_id' ORDER BY `time` DESC LIMIT " . $start . "," . $kmess);
        $mass_read = array();
        for ($i = 0; ($row = mysql_fetch_assoc($req)) !== FALSE; ++$i) {
            $out .= $i % 2 ? '<div class="list1">' : '<div class="list2">';
            if ($row['read'] == 0 && $row['from_id'] == $user_id)
                $mass_read[] = $row['id'];
            $post = $row['text'];
            $post = functions::checkout($post, 1, 1);
            if ($set_user['smileys'])
                $post = functions::smileys($post);
            $out .= '<strong>' . functions::checkout($row['them']) . '</strong> (' . functions::display_date($row['time']) . ')<br />';
            $post = preg_replace_callback("/{TIME=(.+?)}/usi", 'time_parce', $post);
            //print_r($outmass);
            $out .= $post;
            $out .= '<div class="sub"><a href="index.php?act=delete&amp;id=' . $row['id'] . '">' . $lng['delete'] . '</a></div>';
            $out .= '</div>';
        }
        //Ставим метку о прочтении
        if ($mass_read) {
            $result = implode(',', $mass_read);
            mysql_query("UPDATE `cms_mail` SET `read`='1' WHERE `from_id`='$user_id' AND `sys`='1' AND `id` IN (" . $result . ")");
        }
    } else {
        $out .= '<div class="menu"><p>' . $lng['list_empty'] . '</p></div>';
    }

    $out .= '<div class="phdr">' . $lng['total'] . ': ' . $total . '</div>';
    if ($total > $kmess) {
        $out .= '<div class="topmenu">' . functions::display_pagination('index.php?act=systems&amp;', $start, $total, $kmess) . '</div>';
        $out .= '<p><form action="index.php" method="get">
			<input type="hidden" name="act" value="systems"/>
			<input type="text" name="page" size="2"/>
			<input type="submit" value="' . $lng['to_page'] . ' &gt;&gt;"/></form></p>';
    }
}

$textl = $lng['mail'];
require_once('../incfiles/head.php');
echo '<div class="phdr"><b>' . $lng_mail['systems_messages'] . '</b></div>';
echo $out;
echo '<p>';
if ($total) {
    echo '<a href="index.php?act=systems&amp;mod=clear">' . $lng_mail['clear_messages'] . '</a><br/>';
}
echo '<a href="../users/profile.php?act=office">' . $lng['personal'] . '</a></p>';