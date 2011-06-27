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

$sw = 0;
$adm = 0;
$smd = 0;
$mod = 0;
echo '<div class="phdr"><a href="index.php"><b>' . $lng['admin_panel'] . '</b></a> | ' . $lng['administration'] . '</div>';
$req = mysql_query("SELECT * FROM `users` WHERE `rights` = '9' ORDER BY `name` ASC");
if (mysql_num_rows($req)) {
    echo '<div class="bmenu">' . $lng['supervisors'] . '</div>';
    while (($res = mysql_fetch_assoc($req)) !== false) {
        echo $sw % 2 ? '<div class="list2">' : '<div class="list1">';
        echo functions::display_user($res, array('header' => ('<b>ID:' . $res['id'] . '</b>')));
        echo '</div>';
        ++$sw;
    }
}
$req = mysql_query("SELECT * FROM `users` WHERE `rights` = '7' ORDER BY `name` ASC");
if (mysql_num_rows($req)) {
    echo '<div class="bmenu">' . $lng['administrators'] . '</div>';
    while (($res = mysql_fetch_assoc($req)) !== false) {
        echo $adm % 2 ? '<div class="list2">' : '<div class="list1">';
        echo functions::display_user($res, array('header' => ('<b>ID:' . $res['id'] . '</b>')));
        echo '</div>';
        ++$adm;
    }
}
$req = mysql_query("SELECT * FROM `users` WHERE `rights` = '6' ORDER BY `name` ASC");
if (mysql_num_rows($req)) {
    echo '<div class="bmenu">' . $lng['supermoders'] . '</div>';
    while (($res = mysql_fetch_assoc($req)) !== false) {
        echo $smd % 2 ? '<div class="list2">' : '<div class="list1">';
        echo functions::display_user($res, array('header' => ('<b>ID:' . $res['id'] . '</b>')));
        echo '</div>';
        ++$smd;
    }
}
$req = mysql_query("SELECT * FROM `users` WHERE `rights` BETWEEN '1' AND '5' ORDER BY `name` ASC");
if (mysql_num_rows($req)) {
    echo '<div class="bmenu">' . $lng['moders'] . '</div>';
    while (($res = mysql_fetch_assoc($req)) !== false) {
        echo $mod % 2 ? '<div class="list2">' : '<div class="list1">';
        echo functions::display_user($res, array('header' => ('<b>ID:' . $res['id'] . '</b>')));
        echo '</div>';
        ++$mod;
    }
}
echo '<div class="phdr">' . $lng['total'] . ': ' . ($sw + $adm + $smd + $mod) . '</div>' .
    '<p><a href="index.php?act=usr">' . $lng['users_list'] . '</a><br />' .
    '<a href="index.php">' . $lng['admin_panel'] . '</a></p>';

?>