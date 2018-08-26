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
    'admlist'  => 'includes',
    'birth'    => 'includes',
    'online'   => 'includes',
    'search'   => 'includes',
    'top'      => 'includes',
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
    $brth = $db->query("SELECT COUNT(*) FROM `users` WHERE `dayb` = '" . date('j', time()) . "' AND `monthb` = '" . date('n', time()) . "' AND `preg` = '1'")->fetchColumn();
    $count_adm = $db->query("SELECT COUNT(*) FROM `users` WHERE `rights` > 0")->fetchColumn();
    echo '<div class="phdr"><b>' . $lng['community'] . '</b></div>' .
        '<div class="gmenu"><form action="search.php" method="post">' .
        '<p><h3><img src="../images/search.png" width="16" height="16" class="left" />&#160;' . $lng['search'] . '</h3>' .
        '<input type="text" name="search"/>' .
        '<input type="submit" value="' . $lng['search'] . '" name="submit" /><br />' .
        '<small>' . $lng['search_nick_help'] . '</small></p></form></div>' .
        '<div class="menu"><p>' .
        functions::image('contacts.png', array('width' => 16, 'height' => 16)) . '<a href="index.php?act=userlist">' . $lng['users'] . '</a> (' . counters::users() . ')<br />' .
        functions::image('users.png', array('width' => 16, 'height' => 16)) . '<a href="index.php?act=admlist">' . $lng['administration'] . '</a> (' . $count_adm . ')<br/>' .
        ($brth
            ? functions::image('award.png', array('width' => 16, 'height' => 16)) . '<a href="index.php?act=birth">' . $lng['birthday_men'] . '</a> (' . $brth . ')<br/>'
            : '') .
        functions::image('photo.gif', array('width' => 16, 'height' => 16)) . '<a href="album.php">' . $lng['photo_albums'] . '</a> (' . counters::album() . ')<br/>' .
        functions::image('rate.gif', array('width' => 16, 'height' => 16)) . '<a href="index.php?act=top">' . $lng['users_top'] . '</a></p>' .
        '</div>' .
        '<div class="phdr"><a href="index.php">' . $lng['back'] . '</a></div>';
}

require_once('../incfiles/end.php');