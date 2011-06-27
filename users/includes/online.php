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
$headmod = 'online';
$textl = $lng['online'];
//$lng_online = core::load_lng('online');
require('../incfiles/head.php');

/*
-----------------------------------------------------------------
Показываем список Online
-----------------------------------------------------------------
*/
$menu[] = !$mod ? '<b>' . $lng['users'] . '</b>' : '<a href="index.php?act=online">' . $lng['users'] . '</a>';
$menu[] = $mod == 'history' ? '<b>' . $lng['history'] . '</b>' : '<a href="index.php?act=online&amp;mod=history">' . $lng['history'] . '</a> ';
if (core::$user_rights) $menu[] = $mod == 'guest' ? '<b>' . $lng['guests'] . '</b>' : '<a href="index.php?act=online&amp;mod=guest">' . $lng['guests'] . '</a>';
echo '<div class="phdr"><b>' . $lng['who_on_site'] . '</b></div>' .
     '<div class="topmenu">' . functions::display_menu($menu) . '</div>';

switch ($mod) {
    case 'guest':
        // Список гостей Онлайн
        $sql_total = "SELECT COUNT(*) FROM `cms_sessions` WHERE `lastdate` > " . (time() - 300);
        $sql_list = "SELECT * FROM `cms_sessions` WHERE `lastdate` > " . (time() - 300) . " ORDER BY `movings` DESC LIMIT $start, $kmess";
        break;

    case 'history':
        // История посетилелей за последние 2 суток
        $sql_total = "SELECT COUNT(*) FROM `users` WHERE `lastdate` > " . (time() - 172800 . " AND `lastdate` < " . (time() - 310));
        $sql_list = "SELECT * FROM `users` WHERE `lastdate` > " . (time() - 172800) . " AND `lastdate` < " . (time() - 310) . " ORDER BY `sestime` DESC LIMIT $start, $kmess";
        break;

    default:
        // Список посетителей Онлайн
        $sql_total = "SELECT COUNT(*) FROM `users` WHERE `lastdate` > " . (time() - 300);
        $sql_list = "SELECT * FROM `users` WHERE `lastdate` > " . (time() - 300) . " ORDER BY `name` ASC LIMIT $start, $kmess";
}

$total = mysql_result(mysql_query($sql_total), 0);
if ($total > $kmess) echo '<div class="topmenu">' . functions::display_pagination('index.php?act=online&amp;' . ($mod ? 'mod=' . $mod . '&amp;' : ''), $start, $total, $kmess) . '</div>';
if ($total) {
    $req = mysql_query($sql_list);
    $i = 0;
    while (($res = mysql_fetch_assoc($req)) !== false) {
        if ($res['id'] == core::$user_id) echo '<div class="gmenu">';
        else echo $i % 2 ? '<div class="list2">' : '<div class="list1">';
        $arg['stshide'] = 1;
        $arg['header'] = ' <span class="gray">(';
        if($mod == 'history') $arg['header'] .= functions::display_date($res['sestime']);
        else $arg['header'] .= $res['movings'] . ' - ' . functions::timecount(time() - $res['sestime']);
        $arg['header'] .= ')</span><br /><img src="../images/info.png" width="16" height="16" align="middle" />&#160;' . functions::display_place($res['id'], $res['place']);
        echo functions::display_user($res, $arg);
        echo '</div>';
        ++$i;
    }
} else {
    echo '<div class="menu"><p>' . $lng['list_empty'] . '</p></div>';
}
echo '<div class="phdr">' . $lng['total'] . ': ' . $total . '</div>';
if ($total > $kmess) {
    echo '<div class="topmenu">' . functions::display_pagination('index.php?act=online&amp;' . ($mod ? 'mod=' . $mod . '&amp;' : ''), $start, $total, $kmess) . '</div>' .
         '<p><form action="index.php?act=online' . ($mod ? '&amp;mod=' . $mod : '') . '" method="post">' .
         '<input type="text" name="page" size="2"/>' .
         '<input type="submit" value="' . $lng['to_page'] . ' &gt;&gt;"/>' .
         '</form></p>';
}
?>