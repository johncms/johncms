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
$headmod = 'userstop';
$textl = $lng['users_top'];
require('../incfiles/head.php');

/*
-----------------------------------------------------------------
Функция отображения списков
-----------------------------------------------------------------
*/
function get_top($order = 'postforum') {
    global $lng;
    $req = mysql_query("SELECT * FROM `users` WHERE `$order` > 0 ORDER BY `$order` DESC LIMIT 9");
    if (mysql_num_rows($req)) {
        $out = '';
        $i = 0;
        while ($res = mysql_fetch_assoc($req)) {
            $out .= $i % 2 ? '<div class="list2">' : '<div class="list1">';
            $out .= functions::display_user($res, array ('header' => ('<b>' . $res[$order]) . '</b>')) . '</div>';
            ++$i;
        }
        return $out;
    } else {
        return '<div class="menu"><p>' . $lng['list_empty'] . '</p></div>';
    }
}

/*
-----------------------------------------------------------------
Меню выбора
-----------------------------------------------------------------
*/
$menu = array (
    (!$mod ? '<b>' . $lng['forum'] . '</b>' : '<a href="index.php?act=top">' . $lng['forum'] . '</a>'),
    ($mod == 'guest' ? '<b>' . $lng['guestbook'] . '</b>' : '<a href="index.php?act=top&amp;mod=guest">' . $lng['guestbook'] . '</a>'),
    ($mod == 'comm' ? '<b>' . $lng['comments'] . '</b>' : '<a href="index.php?act=top&amp;mod=comm">' . $lng['comments'] . '</a>')
);
if ($set_karma['on'])
    $menu[] = $mod == 'karma' ? '<b>' . $lng['karma'] . '</b>' : '<a href="index.php?act=top&amp;mod=karma">' . $lng['karma'] . '</a>';
switch ($mod) {
    case 'guest':
        /*
        -----------------------------------------------------------------
        Топ Гостевой
        -----------------------------------------------------------------
        */
        echo '<div class="phdr"><a href="index.php"><b>' . $lng['community'] . '</b></a> | ' . $lng['top_guest'] . '</div>';
        echo '<div class="topmenu">' . functions::display_menu($menu) . '</div>';
        echo get_top('postguest');
        echo '<div class="phdr"><a href="../guestbook/index.php">' . $lng['guestbook'] . '</a></div>';
        break;

    case 'comm':
        /*
        -----------------------------------------------------------------
        Топ комментариев
        -----------------------------------------------------------------
        */
        echo '<div class="phdr"><a href="index.php"><b>' . $lng['community'] . '</b></a> | ' . $lng['top_comm'] . '</div>';
        echo '<div class="topmenu">' . functions::display_menu($menu) . '</div>';
        echo get_top('komm');
        echo '<div class="phdr"><a href="../index.php">' . $lng['homepage'] . '</a></div>';
        break;

    case 'karma':
        /*
        -----------------------------------------------------------------
        Топ Кармы
        -----------------------------------------------------------------
        */
        if ($set_karma['on']) {
            echo '<div class="phdr"><a href="index.php"><b>' . $lng['community'] . '</b></a> | ' . $lng['top_karma'] . '</div>';
            echo '<div class="topmenu">' . functions::display_menu($menu) . '</div>';
            $req = mysql_query("SELECT *, (`karma_plus` - `karma_minus`) AS `karma` FROM `users` WHERE (`karma_plus` - `karma_minus`) > 0 ORDER BY `karma` DESC LIMIT 9");
            if (mysql_num_rows($req)) {
                $i = 0;
                while ($res = mysql_fetch_assoc($req)) {
                    echo $i % 2 ? '<div class="list2">' : '<div class="list1">';
                    echo functions::display_user($res, array ('header' => ('<b>' . $res['karma']) . '</b>')) . '</div>';
                    ++$i;
                }
            } else {
                echo '<div class="menu"><p>' . $lng['list_empty'] . '</p></div>';
            }
            echo '<div class="phdr"><a href="../index.php">' . $lng['homepage'] . '</a></div>';
        }
        break;

    default:
        /*
        -----------------------------------------------------------------
        Топ Форума
        -----------------------------------------------------------------
        */
        echo '<div class="phdr"><a href="index.php"><b>' . $lng['community'] . '</b></a> | ' . $lng['top_forum'] . '</div>';
        echo '<div class="topmenu">' . functions::display_menu($menu) . '</div>';
        echo get_top('postforum');
        echo '<div class="phdr"><a href="../forum/index.php">' . $lng['forum'] . '</a></div>';
}
echo '<p><a href="index.php">' . $lng['back'] . '</a></p>';
?>