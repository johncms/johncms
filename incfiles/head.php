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
$headmod = isset($headmod) ? mysql_real_escape_string($headmod) : '';
if ($headmod == 'mainpage')
    $textl = $set['copyright'];

/*
-----------------------------------------------------------------
Выводим HTML заголовки страницы, подключаем CSS файл
-----------------------------------------------------------------
*/
header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");
header("Cache-Control: no-cache, must-revalidate");
header("Pragma: no-cache");
header("Last-Modified: " . gmdate("D, d M Y H:i:s") . "GMT");
header((stristr($agn, "msie") && stristr($agn, "windows")) ? 'Content-type: text/html; charset=UTF-8' : 'Content-type: application/xhtml+xml; charset=UTF-8');
echo '<?xml version="1.0" encoding="utf-8"?>' . "\n" .
    "\n" . '<!DOCTYPE html PUBLIC "-//WAPFORUM//DTD XHTML Mobile 1.0//EN" "http://www.wapforum.org/DTD/xhtml-mobile10.dtd">' .
    "\n" . '<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="ru">' .
    "\n" . '<head><meta http-equiv="content-type" content="application/xhtml+xml; charset=utf-8"/>' .
    "\n" . '<link rel="shortcut icon" href="' . $set['homeurl'] . '/favicon.ico" />' .
    "\n" . '<meta name="Generator" content="JohnCMS, http://johncms.com" />'; // ВНИМАНИЕ!!! Данный копирайт удалять нельзя
if (!empty($set['meta_key']))
    echo "\n" . '<meta name="keywords" content="' . $set['meta_key'] . '" />';
if (!empty($set['meta_desc']))
    echo "\n" . '<meta name="description" content="' . $set['meta_desc'] . '" />';
echo "\n" . '<link rel="alternate" type="application/rss+xml" title="RSS | ' . $lng['site_news'] . '" href="' . $set['homeurl'] . '/rss/rss.php" />' .
    "\n" . '<title>' . $textl . '</title>' .
    "\n" . '<link rel="stylesheet" href="' . $set['homeurl'] . '/theme/' . $set_user['skin'] . '/style.css" type="text/css" />' .
    "\n" . '</head><body>';

/*
-----------------------------------------------------------------
Рекламный модуль
-----------------------------------------------------------------
*/
$cms_ads = array ();
$view = $user_id ? 2 : 1;
$layout = ($headmod == 'mainpage' && !$act) ? 1 : 2;
$req = mysql_query("SELECT * FROM `cms_ads` WHERE `to` = '0' AND (`layout` = '$layout' or `layout` = '0') AND (`view` = '$view' or `view` = '0') ORDER BY  `mesto` ASC");
if (mysql_num_rows($req) > 0 && $headmod != 'admin') {
    while ($res = mysql_fetch_array($req)) {
        $name = explode("|", $res['name']);
        $name = htmlentities($name[mt_rand(0, (count($name) - 1))], ENT_QUOTES, 'UTF-8');
        if (!empty($res['color']))
            $name = '<span style="color:#' . $res['color'] . '">' . $name . '</span>';
        // Если было задано начертание шрифта, то применяем
        $font = $res['bold'] ? 'font-weight: bold;' : false;
        $font .= $res['italic'] ? ' font-style:italic;' : false;
        $font .= $res['underline'] ? ' text-decoration:underline;' : false;
        if ($font)
            $name = '<span style="' . $font . '">' . $name . '</span>';
        $cms_ads[$res['type']] .= '<a href="' . ($res['show'] ? functions::checkout($res['link']) : $set['homeurl'] . '/go.php?id=' . $res['id']) . '">' . $name . '</a><br/>';
        if (($res['day'] != 0 && $realtime >= ($res['time'] + $res['day'] * 3600 * 24)) || ($res['count_link'] != 0 && $res['count'] >= $res['count_link']))
            mysql_query("UPDATE `cms_ads` SET `to` = '1'  WHERE `id` = '" . $res['id'] . "'");
    }
}

/*
-----------------------------------------------------------------
Рекламный блок сайта
-----------------------------------------------------------------
*/
if ($cms_ads[0])
    echo $cms_ads[0];

/*
-----------------------------------------------------------------
Выводим логотип
-----------------------------------------------------------------
*/
echo '<div><a href="' . $set['homeurl'] . '"><img src="' . $set['homeurl'] . '/theme/' . $set_user['skin'] . '/images/logo.gif" alt="" border="0"/></a></div>';

/*
-----------------------------------------------------------------
Выводим верхний блок с приветствием
-----------------------------------------------------------------
*/
echo '<div class="header"> ' . $lng['hi'] . ', ' . ($user_id ? '<b>' . $login . '</b>!' : $lng['guest'] . '!') . '</div>';

/*
-----------------------------------------------------------------
Выводим главное меню пользователя
-----------------------------------------------------------------
*/
echo '<div class="tmn">';
echo ($headmod != "mainpage" || ($headmod == 'mainpage' && $act)) ? '<a href=\'' . $set['homeurl'] . '\'>' . $lng['homepage'] . '</a> | ' : '';
echo ($user_id) ? '<a href="' . $set['homeurl'] . '/users/profile.php?act=office">' . $lng['personal'] . '</a> | ' : '';
echo $user_id
    ? '<a href="' . $set['homeurl'] . '/exit.php">' . $lng['exit'] . '</a>' : '<a href="' . $set['homeurl'] . '/login.php">' . $lng['login'] . '</a> | <a href="' . $set['homeurl'] . '/registration.php">' . $lng['registration'] . '</a>';
echo '</div><div class="maintxt">';

/*
-----------------------------------------------------------------
Рекламный блок сайта
-----------------------------------------------------------------
*/
if (!empty($cms_ads[1]))
    echo '<div class="gmenu">' . $cms_ads[1] . '</div>';

/*
-----------------------------------------------------------------
Фиксация местоположений посетителей
-----------------------------------------------------------------
*/
$sql = '';
$set_karma = unserialize($set['karma']);
if ($user_id) {
    // Фиксируем местоположение авторизованных
    if (!$datauser['karma_off'] && $set_karma['on'] && $datauser['karma_time'] <= ($realtime - 86400)) {
        $sql = "`karma_time` = '$realtime', ";
    }
    $movings = $datauser['movings'];
    if ($datauser['lastdate'] < ($realtime - 300)) {
        $movings = 0;
        $sql .= "`sestime` = '$realtime',";
    }
    if ($datauser['place'] != $headmod) {
        $movings = $movings + 1;
        $sql .= "`movings` = '$movings', `place` = '$headmod',";
    }
    if ($datauser['browser'] != $agn)
        $sql .= "`browser` = '" . mysql_real_escape_string($agn) . "',";
    $totalonsite = $datauser['total_on_site'];
    if ($datauser['lastdate'] > ($realtime - 300))
        $totalonsite = $totalonsite + $realtime - $datauser['lastdate'];
    mysql_query("UPDATE `users` SET $sql
        `total_on_site` = '$totalonsite',
        `lastdate` = '$realtime'
        WHERE `id` = '$user_id'
    ");
} else {
    // Фиксируем местоположение гостей
    $sid = md5($ip . $agn);
    $movings = 0;
    $req = mysql_query("SELECT * FROM `cms_guests` WHERE `session_id` = '$sid' LIMIT 1");
    if (mysql_num_rows($req)) {
        // Если есть в базе, то обновляем данные
        $res = mysql_fetch_assoc($req);
        $movings = $res['movings'];
        if ($res['sestime'] < ($realtime - 300)) {
            $movings = 0;
            $sql .= "`sestime` = '$realtime',";
        }
        if ($res['ip'] != $ip)
            $sql .= "`ip` = '$ip',";
        if ($res['browser'] != $agn)
            $sql .= "`browser` = '" . mysql_real_escape_string($agn) . "',";
        if ($res['place'] != $headmod) {
            $movings = $movings + 1;
            $sql .= "`movings` = '$movings', `place` = '$headmod',";
        }
        mysql_query("UPDATE `cms_guests` SET $sql
        `lastdate` = '$realtime'
        WHERE `session_id` = '$sid'");
    } else {
        // Если еще небыло в базе, то добавляем запись
        mysql_query("INSERT INTO `cms_guests` SET
            `session_id` = '$sid',
            `ip` = '$ip',
            `browser` = '" . mysql_real_escape_string($agn) . "',
            `lastdate` = '$realtime',
            `sestime` = '$realtime',
            `place` = '$headmod'
        ");
    }
}

/*
-----------------------------------------------------------------
Выводим сообщение о Бане
-----------------------------------------------------------------
*/
if (!empty($ban))
    echo '<div class="alarm">' . $lng['ban'] . '&#160;<a href="' . $set['homeurl'] . '/users/profile.php?act=ban">' . $lng['in_detail'] . '</a></div>';

/*
-----------------------------------------------------------------
Ссылки на непрочитанное
-----------------------------------------------------------------
*/
if ($user_id) {
    $list = array ();
    if ($headmod != "pradd") {
        $new_mail = mysql_result(mysql_query("SELECT COUNT(*) FROM `privat` WHERE `user` = '$login' AND `type` = 'in' AND `chit` = 'no'"), 0);
        if ($new_mail)
            $list[] = '<a href="' . $set['homeurl'] . '/users/pradd.php?act=in&amp;new">' . $lng['mail'] . '</a>&#160;(' . $new_mail . ')';
    }
    if ($datauser['comm_count'] > $datauser['comm_old']) {
        $list[] = '<a href="' . $set['homeurl'] . '/users/profile.php?act=guestbook&amp;user=' . $user_id . '">' . $lng['guestbook'] . '</a> (' . ($datauser['comm_count'] - $datauser['comm_old']) . ')';
    }
    if(!empty($list))
        echo '<div class="rmenu">' . $lng['unread'] . ': ' . functions::display_menu($list, ', ') . '</div>';
}
?>