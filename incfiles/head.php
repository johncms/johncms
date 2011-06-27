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

$headmod = isset($headmod) ? mysql_real_escape_string($headmod) : '';
$textl = isset($textl) ? $textl : $set['copyright'];

/*
-----------------------------------------------------------------
Выводим HTML заголовки страницы, подключаем CSS файл
-----------------------------------------------------------------
*/
if(stristr(core::$user_agent, "msie") && stristr(core::$user_agent, "windows")){
    // Выдаем заголовки для Internet Explorer
    header("Cache-Control: no-store, no-cache, must-revalidate");
    header('Content-type: text/html; charset=UTF-8');
} else {
    // Выдаем заголовки для остальных браузеров
    header("Cache-Control: public");
    header('Content-type: application/xhtml+xml; charset=UTF-8');
}
header("Expires: " . date("r",  time() + 60));
echo '<?xml version="1.0" encoding="utf-8"?>' . "\n" .
     "\n" . '<!DOCTYPE html PUBLIC "-//WAPFORUM//DTD XHTML Mobile 1.0//EN" "http://www.wapforum.org/DTD/xhtml-mobile10.dtd">' .
     "\n" . '<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="ru">' .
     "\n" . '<head>' .
     "\n" . '<meta http-equiv="content-type" content="application/xhtml+xml; charset=utf-8"/>' .
     "\n" . '<meta http-equiv="Content-Style-Type" content="text/css" />' .
     "\n" . '<meta name="Generator" content="JohnCMS, http://johncms.com" />' . // ВНИМАНИЕ!!! Данный копирайт удалять нельзя
     (!empty($set['meta_key']) ? "\n" . '<meta name="keywords" content="' . $set['meta_key'] . '" />' : '') .
     (!empty($set['meta_desc']) ? "\n" . '<meta name="description" content="' . $set['meta_desc'] . '" />' : '') .
     "\n" . '<link rel="stylesheet" href="' . $set['homeurl'] . '/theme/' . $set_user['skin'] . '/style.css" type="text/css" />' .
     "\n" . '<link rel="shortcut icon" href="' . $set['homeurl'] . '/favicon.ico" />' .
     "\n" . '<link rel="alternate" type="application/rss+xml" title="RSS | ' . $lng['site_news'] . '" href="' . $set['homeurl'] . '/rss/rss.php" />' .
     "\n" . '<title>' . $textl . '</title>' .
     "\n" . '</head><body>' . core::display_core_errors();

/*
-----------------------------------------------------------------
Рекламный модуль
-----------------------------------------------------------------
*/
$cms_ads = array();
if (!isset($_GET['err']) && $act != '404' && $headmod != 'admin') {
    $view = $user_id ? 2 : 1;
    $layout = ($headmod == 'mainpage' && !$act) ? 1 : 2;
    $req = mysql_query("SELECT * FROM `cms_ads` WHERE `to` = '0' AND (`layout` = '$layout' or `layout` = '0') AND (`view` = '$view' or `view` = '0') ORDER BY  `mesto` ASC");
    if (mysql_num_rows($req)) {
        while (($res = mysql_fetch_assoc($req)) !== false) {
            $name = explode("|", $res['name']);
            $name = htmlentities($name[mt_rand(0, (count($name) - 1))], ENT_QUOTES, 'UTF-8');
            if (!empty($res['color'])) $name = '<span style="color:#' . $res['color'] . '">' . $name . '</span>';
            // Если было задано начертание шрифта, то применяем
            $font = $res['bold'] ? 'font-weight: bold;' : false;
            $font .= $res['italic'] ? ' font-style:italic;' : false;
            $font .= $res['underline'] ? ' text-decoration:underline;' : false;
            if ($font) $name = '<span style="' . $font . '">' . $name . '</span>';
            @$cms_ads[$res['type']] .= '<a href="' . ($res['show'] ? functions::checkout($res['link']) : $set['homeurl'] . '/go.php?id=' . $res['id']) . '">' . $name . '</a><br/>';
            if (($res['day'] != 0 && time() >= ($res['time'] + $res['day'] * 3600 * 24)) || ($res['count_link'] != 0 && $res['count'] >= $res['count_link']))
                mysql_query("UPDATE `cms_ads` SET `to` = '1'  WHERE `id` = '" . $res['id'] . "'");
        }
    }
}

/*
-----------------------------------------------------------------
Рекламный блок сайта
-----------------------------------------------------------------
*/
if (isset($cms_ads[0])) echo $cms_ads[0];

/*
-----------------------------------------------------------------
Выводим логотип и переключатель языков
-----------------------------------------------------------------
*/
echo '<table style="width: 100%;"><tr>' .
     '<td valign="bottom"><a href="' . $set['homeurl'] . '"><img src="' . $set['homeurl'] . '/theme/' . $set_user['skin'] . '/images/logo.gif" alt=""/></a></td>' .
     ($headmod == 'mainpage' && count(core::$lng_list) > 1 ? '<td align="right"><a href="' . $set['homeurl'] . '/go.php?lng"><b>' . strtoupper(core::$lng_iso) . '</b></a>&#160;<img src="' . $set['homeurl'] . '/images/flags/' . core::$lng_iso . '.gif" alt=""/>&#160;</td>' : '') .
     '</tr></table>';

/*
-----------------------------------------------------------------
Выводим верхний блок с приветствием
-----------------------------------------------------------------
*/
echo '<div class="header"> ' . $lng['hi'] . ', ' . ($user_id ? '<b>' . $login . '</b>!' : $lng['guest'] . '!') . '</div>';

/*
-----------------------------------------------------------------
Главное меню пользователя
-----------------------------------------------------------------
*/
echo '<div class="tmn">' .
     (isset($_GET['err']) || $headmod != "mainpage" || ($headmod == 'mainpage' && $act) ? '<a href=\'' . $set['homeurl'] . '\'>' . $lng['homepage'] . '</a> | ' : '') .
     ($user_id ? '<a href="' . $set['homeurl'] . '/users/profile.php?act=office">' . $lng['personal'] . '</a> | ' : '') .
     ($user_id ? '<a href="' . $set['homeurl'] . '/exit.php">' . $lng['exit'] . '</a>' : '<a href="' . $set['homeurl'] . '/login.php">' . $lng['login'] . '</a> | <a href="' . $set['homeurl'] . '/registration.php">' . $lng['registration'] . '</a>') .
     '</div><div class="maintxt">';

/*
-----------------------------------------------------------------
Рекламный блок сайта
-----------------------------------------------------------------
*/
if (!empty($cms_ads[1])) echo '<div class="gmenu">' . $cms_ads[1] . '</div>';

/*
-----------------------------------------------------------------
Фиксация местоположений посетителей
-----------------------------------------------------------------
*/
$sql = '';
$set_karma = unserialize($set['karma']);
if ($user_id) {
    // Фиксируем местоположение авторизованных
    if (!$datauser['karma_off'] && $set_karma['on'] && $datauser['karma_time'] <= (time() - 86400)) {
        $sql = "`karma_time` = '" . time() . "', ";
    }
    $movings = $datauser['movings'];
    if ($datauser['lastdate'] < (time() - 300)) {
        $movings = 0;
        $sql .= "`sestime` = '" . time() . "',";
    }
    if ($datauser['place'] != $headmod) {
        ++$movings;
        $sql .= "`place` = '$headmod',";
    }
    if ($datauser['browser'] != $agn)
        $sql .= "`browser` = '" . mysql_real_escape_string($agn) . "',";
    $totalonsite = $datauser['total_on_site'];
    if ($datauser['lastdate'] > (time() - 300))
        $totalonsite = $totalonsite + time() - $datauser['lastdate'];
    mysql_query("UPDATE `users` SET $sql
        `movings` = '$movings',
        `total_on_site` = '$totalonsite',
        `lastdate` = '" . time() . "'
        WHERE `id` = '$user_id'
    ");
} else {
    // Фиксируем местоположение гостей
    $movings = 0;
    $session = md5(core::$ip . core::$ip_via_proxy . core::$user_agent);
    $req = mysql_query("SELECT * FROM `cms_sessions` WHERE `session_id` = '$session' LIMIT 1");
    if (mysql_num_rows($req)) {
        // Если есть в базе, то обновляем данные
        $res = mysql_fetch_assoc($req);
        $movings = $res['movings'];
        if ($res['sestime'] < (time() - 300)) {
            $movings = 0;
            $sql .= "`sestime` = '" . time() . "', `movings` = '0'";
        }
        if ($res['place'] != $headmod) {
            ++$movings;
            $sql .= "`place` = '$headmod',";
        }
        mysql_query("UPDATE `cms_sessions` SET $sql
            `movings` = '$movings',
            `lastdate` = '" .time()  . "'
            WHERE `session_id` = '$session'
        ");
    } else {
        // Если еще небыло в базе, то добавляем запись
        mysql_query("INSERT INTO `cms_sessions` SET
            `session_id` = '" . $session . "',
            `ip` = '" . core::$ip . "',
            `ip_via_proxy` = '" . core::$ip_via_proxy . "',
            `browser` = '" . mysql_real_escape_string($agn) . "',
            `lastdate` = '" . time() . "',
            `sestime` = '" . time() . "',
            `place` = '$headmod'
        ");
    }
}

/*
-----------------------------------------------------------------
Выводим сообщение о Бане
-----------------------------------------------------------------
*/
if (!empty($ban)) echo '<div class="alarm">' . $lng['ban'] . '&#160;<a href="' . $set['homeurl'] . '/users/profile.php?act=ban">' . $lng['in_detail'] . '</a></div>';

/*
-----------------------------------------------------------------
Ссылки на непрочитанное
-----------------------------------------------------------------
*/
if ($user_id) {
    $list = array();
    $new_mail = mysql_result(mysql_query("SELECT COUNT(*) FROM `privat` WHERE `user` = '$login' AND `type` = 'in' AND `chit` = 'no'"), 0);
    if ($new_mail) $list[] = '<a href="' . $set['homeurl'] . '/users/pradd.php?act=in&amp;new">' . $lng['mail'] . '</a>&#160;(' . $new_mail . ')';
    if ($datauser['comm_count'] > $datauser['comm_old']) $list[] = '<a href="' . $set['homeurl'] . '/users/profile.php?act=guestbook&amp;user=' . $user_id . '">' . $lng['guestbook'] . '</a> (' . ($datauser['comm_count'] - $datauser['comm_old']) . ')';
    if (!empty($list)) echo '<div class="rmenu">' . $lng['unread'] . ': ' . functions::display_menu($list, ', ') . '</div>';
}
?>