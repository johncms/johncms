<?php

/**
* @package     JohnCMS
* @link        http://johncms.com
* @copyright   Copyright (C) 2008-2011 JohnCMS Community
* @license     LICENSE.txt (see attached file)
* @version     VERSION.txt (see attached file)
* @author      http://johncms.com/about
*/

define('_IN_JOHNCMS', 1);

$headmod = 'mainpage';
$rootpath = ''; // Внимание! Если файл находится в корневой папке, нужно указать $rootpath = '';
require('incfiles/core.php');
require('incfiles/head.php');

if (isset($_SESSION['ref']))
    unset($_SESSION['ref']);
if (isset($_GET['err']))
    $act = 404;

switch ($act) {
    case '404':
        /*
        -----------------------------------------------------------------
        Сообщение об ошибке 404
        -----------------------------------------------------------------
        */
        echo functions::display_error($lng['error_404']);
        break;

    case 'digest':
        /*
        -----------------------------------------------------------------
        Дайджест
        -----------------------------------------------------------------
        */
        if (!$user_id) {
            echo functions::display_error($lng['access_guest_forbidden']);
            require_once('incfiles/end.php');
            exit;
        }
        echo '<div class="phdr"><b>' . $lng['digest'] . '</b></div>';
        echo '<div class="gmenu"><p>' . $lng['hi'] . ', <b>' . $login . '</b><br/>' . $lng['welcome_to'] . ' ' . $set['copyright'] . '!<br /><a href="index.php">' . $lng['enter_on_site'] . '</a></p></div>';
        // Поздравление с днем рождения
        if ($datauser['dayb'] == date('j', time()) && $datauser['monthb'] == date('n', time())) {
            echo '<div class="rmenu"><p>' . $lng['happy_birthday'] . '</p></div>';
        }
        // Дайджест Администратора
        if ($rights >= 1) {
            $new_users_total = mysql_result(mysql_query("SELECT COUNT(*) FROM `users` WHERE `datereg` > '" . (time() - 86400) . "' AND `preg` = '1'"), 0);
            $reg_total = mysql_result(mysql_query("SELECT COUNT(*) FROM `users` WHERE `preg` = 0"), 0);
            $ban_total = mysql_result(mysql_query("SELECT COUNT(*) FROM `cms_ban_users` WHERE `ban_time` > '" . time() . "'"), 0);
            echo '<div class="menu"><p><h3>' . $lng['administrative_events'] . '</h3><ul>';
            if ($new_users_total > 0)
                echo '<li><a href="users/index.php?act=userlist">' . $lng['users_new'] . '</a> (' . $new_users_total . ')</li>';
            if ($reg_total > 0)
                echo '<li><a href="' . $set['admp'] . '/index.php?act=reg">' . $lng['users_on_reg'] . '</a> (' . $reg_total . ')</li>';
            if ($ban_total > 0)
                echo '<li><a href="' . $set['admp'] . '/index.php?act=ban_panel">' . $lng['users_on_ban'] . '</a> (' . $ban_total . ')</li>';
            $total_libmod = mysql_result(mysql_query("SELECT COUNT(*) FROM `lib` WHERE `type` = 'bk' AND `moder` = 0"), 0);
            if ($total_libmod > 0)
                echo '<li><a href="library/index.php?act=moder">' . $lng['library_on_moderation'] . '</a> (' . $total_libmod . ')</li>';
            $total_admin = counters::guestbook(2);
            if ($total_admin > 0)
                echo '<li><a href="guestbook/index.php?act=ga&amp;do=set">' . $lng['admin_club'] . '</a> (' . $total_admin . ')</li>';
            if (!$new_users_total && !$reg_total && !$ban_total && !$total_libmod && !$total_admin)
                echo '<li>' . $lng['events_no_new'] . '</li>';
            echo '</ul></p></div>';
        }
        // Дайджест юзеров
        echo '<div class="menu"><p><h3>' . $lng['site_new'] . '</h3><ul>';
        $total_news = mysql_result(mysql_query("SELECT COUNT(*) FROM `news` WHERE `time` > " . (time() - 86400)), 0);
        if ($total_news > 0)
            echo '<li><a href="news/index.php">' . $lng['news'] . '</a> (' . $total_news . ')</li>';
        $total_forum = counters::forum_new();
        if ($total_forum > 0)
            echo '<li><a href="forum/index.php?act=new">' . $lng['forum'] . '</a> (' . $total_forum . ')</li>';
        $total_guest = counters::guestbook(1);
        if ($total_guest > 0)
            echo '<li><a href="guestbook/index.php?act=ga">' . $lng['guestbook'] . '</a> (' . $total_guest . ')</li>';
        $total_gal = counters::gallery(1);
        if ($total_gal > 0)
            echo '<li><a href="gallery/index.php?act=new">' . $lng['gallery'] . '</a> (' . $total_gal . ')</li>';
        if ($set_karma['on']) {
            $total_karma = mysql_result(mysql_query("SELECT COUNT(*) FROM `karma_users` WHERE `karma_user` = '$user_id' AND `time` > " . (time() - 86400)), 0);
            if ($total_karma > 0)
                echo '<li><a href="users/profile.php?act=karma&amp;mod=new">' . $lng['new_responses'] . '</a> (' . $total_karma . ')</li>';
        }
        $total_lib = mysql_result(mysql_query("SELECT COUNT(*) FROM `lib` WHERE `type` = 'bk' AND `moder` = 1 AND `time` > " . (time() - 259200)), 0);
        if ($total_lib > 0)
            echo '<li><a href="library/index.php?act=new">' . $lng['library'] . '</a> (' . $total_lib . ')</li>';
        $total_album = mysql_result(mysql_query("SELECT COUNT(*) FROM `cms_album_files` WHERE `time` > '" . (time() - 259200) . "' AND `access` > '1'"), 0);
            if($total_album > 0) echo '<li><a href="users/album.php?act=top">' . $lng['photo_albums'] . '</a> (' . $total_album . ')</li>';
        // Если нового нет, выводим сообщение
        if (!$total_news && !$total_forum && !$total_guest && !$total_gal && !$total_lib && !$total_karma)
            echo '<li>' . $lng['events_no_new'] . '</li>';
        // Дата последнего посещения
        $last = isset($_GET['last']) ? intval($_GET['last']) : $datauser['lastdate'];
        echo '</ul></p></div><div class="phdr">' . $lng['last_visit'] . ': ' . date("d.m.Y (H:i)", $last) . '</div>';
        break;

    default:
        /*
        -----------------------------------------------------------------
        Главное меню сайта
        -----------------------------------------------------------------
        */
        if (isset($_SESSION['ref']))
            unset($_SESSION['ref']);
        include 'pages/mainmenu.php';

        /*
        -----------------------------------------------------------------
        Карта сайта
        -----------------------------------------------------------------
        */
        $set_map = isset($set['sitemap']) ? unserialize($set['sitemap']) : array();
        if (($set_map['forum'] || $set_map['lib']) && ($set_map['users'] || !$user_id) && ($set_map['browsers'] || !$is_mobile)) {
            $map = new sitemap();
            echo '<div class="sitemap">' . $map->site() . '</div>';
        }
}
require('incfiles/end.php');
?>
