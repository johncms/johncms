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

$headmod = 'users';
require('../incfiles/core.php');

/*
-----------------------------------------------------------------
Закрываем от неавторизованных юзеров
-----------------------------------------------------------------
*/
if (!$user_id && !$set['active']) {
    require('../incfiles/head.php');
    echo functions::display_error($lng['access_guest_forbidden']);
    require('../incfiles/end.php');
    exit;
}

/*
-----------------------------------------------------------------
Переключаем режимы работы
-----------------------------------------------------------------
*/
$array = array(
    'admlist' => 'includes',
    'birth' => 'includes',
    'online' => 'includes',
    'search' => 'includes',
    'top' => 'includes',
    'userlist' => 'includes'
);
$path = !empty($array[$act]) ? $array[$act] . '/' : '';
if (array_key_exists($act, $array) && file_exists($path . $act . '.php')) {
    require_once($path . $act . '.php');
} else {
    /*
    -----------------------------------------------------------------
    Актив сайта
    -----------------------------------------------------------------
    */
    $textl = $lng['community'];
    require('../incfiles/head.php');
    $shift = (core::$system_set['timeshift'] + core::$user_set['timeshift']) * 3600;
    $brth = mysql_result(mysql_query("SELECT COUNT(*) FROM `users` WHERE `dayb` = '" . date('j', time() + $shift) . "' AND `monthb` = '" . date('n', time() + $shift) . "' AND `preg` = '1'"), 0);
    $count_adm = mysql_result(mysql_query("SELECT COUNT(*) FROM `users` WHERE `rights` > 0"), 0);
    echo '<div class="phdr"><b>' . $lng['community'] . '</b></div>' .
         '<div class="gmenu"><form action="search.php" method="post">' .
         '<p><h3><img src="../images/search.png" width="16" height="16" class="left" />&#160;' . $lng['search'] . '</h3>' .
         '<input type="text" name="search"/>' .
         '<input type="submit" value="' . $lng['search'] . '" name="submit" /><br />' .
         '<small>' . $lng['search_nick_help'] . '</small></p></form></div>' .
         '<div class="menu"><p>' .
         '<img src="../images/contacts.png" width="16" height="16" />&#160;<a href="index.php?act=userlist">' . $lng['users'] . '</a> (' . counters::users() . ')<br />' .
         '<img src="../images/users.png" width="16" height="16" />&#160;<a href="index.php?act=admlist">' . $lng['administration'] . '</a> (' . $count_adm . ')' .
         ($brth
                 ? '<br /><img src="../images/award.png" width="16" height="16" />&#160;<a href="index.php?act=birth">' . $lng['birthday_men'] . '</a> (' . $brth . ')'
                 : '') .
         '</p><p><img src="../images/photo.gif" width="16" height="16" />&#160;<a href="album.php">' . $lng['photo_albums'] . '</a> (' . counters::album() . ')</p>' .
         '<p><img src="../images/rate.gif" width="16" height="16" />&#160;<a href="index.php?act=top">' . $lng['users_top'] . '</a></p>' .
         '</div>' .
         '<div class="phdr"><a href="index.php">' . $lng['back'] . '</a></div>';
}

require_once('../incfiles/end.php');
?>