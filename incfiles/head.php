<?php

/*
////////////////////////////////////////////////////////////////////////////////
// JohnCMS                             Content Management System              //
// Официальный сайт сайт проекта:      http://johncms.com                     //
// Дополнительный сайт поддержки:      http://gazenwagen.com                  //
////////////////////////////////////////////////////////////////////////////////
// JohnCMS core team:                                                         //
// Евгений Рябинин aka john77          john77@gazenwagen.com                  //
// Олег Касьянов aka AlkatraZ          alkatraz@gazenwagen.com                //
//                                                                            //
// Информацию о версиях смотрите в прилагаемом файле version.txt              //
////////////////////////////////////////////////////////////////////////////////
*/

defined('_IN_JOHNCMS') or die('Error: restricted access');

$headmod = isset ($headmod) ? mysql_real_escape_string($headmod) : '';
if ($headmod == 'mainpage')
    $textl = $copyright;

header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");
header("Cache-Control: no-cache, must-revalidate");
header("Pragma: no-cache");
header("Last-Modified: " . gmdate("D, d M Y H:i:s") . "GMT");
header((stristr($agn, "msie") && stristr($agn, "windows")) ? 'Content-type: text/html; charset=UTF-8' : 'Content-type: application/xhtml+xml; charset=UTF-8');
echo '<?xml version="1.0" encoding="utf-8"?>' . "\n";
echo "\n" . '<!DOCTYPE html PUBLIC "-//WAPFORUM//DTD XHTML Mobile 1.0//EN" "http://www.wapforum.org/DTD/xhtml-mobile10.dtd">';
echo "\n" . '<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="ru">';
echo "\n" . '<head><meta http-equiv="content-type" content="application/xhtml+xml; charset=utf-8"/>';
echo "\n" . '<link rel="shortcut icon" href="' . $home . '/favicon.ico" />';
echo "\n" . '<meta name="copyright" content="Powered by JohnCMS" />';// ВНИМАНИЕ!!! Данный копирайт удалять нельзя
if (!empty ($set['meta_key']))
    echo "\n" . '<meta name="keywords" content="' . $set['meta_key'] . '" />';
if (!empty ($set['meta_desc']))
    echo "\n" . '<meta name="description" content="' . $set['meta_desc'] . '" />';
echo "\n" . '<link rel="alternate" type="application/rss+xml" title="RSS | Новости ресурса" href="' . $home . '/rss/rss.php" />';
echo "\n" . '<title>' . $textl . '</title>';
echo "\n" . '<link rel="stylesheet" href="' . $home . '/theme/' . $set_user['skin'] . '/style.css" type="text/css" />';
echo "\n" . '</head><body>';

////////////////////////////////////////////////////////////
// Рекламный модуль от FlySelf                            //
////////////////////////////////////////////////////////////
$view = $user_id ? 2 : 1;
$layout = ($headmod == 'mainpage' && !$act) ? 1 : 2;
$req = mysql_query("SELECT * FROM `cms_ads` WHERE `to` = '0' AND (`layout` = '$layout' or `layout` = '0') AND (`view` = '$view' or `view` = '0') ORDER BY  `mesto` ASC");
if (mysql_num_rows($req) > 0) {
    $array = array(2 => 'font-weight: bold;', 3 => 'font-style:italic;', 4 => 'text-decoration:underline;', 5 => 'font-weight: bold;font-style:italic;', 6 => 'font-weight: bold;text-decoration:underline;', 7 =>
    'font-style:italic;text-decoration:underline;', 9 => 'font-weight: bold;font-style:italic;text-decoration:underline;');
    $cms_ads = array();
    while ($res = mysql_fetch_array($req)) {
        $name = explode("|", $res['name']);
        $name = htmlentities($name[mt_rand(0, (count($name) - 1))], ENT_QUOTES, 'UTF-8');
        if (!empty ($res['color']))
            $name = '<span style="color:#' . $res['color'] . '">' . $name . '</span>';
        if (!empty ($res['font']))
            $name = '<span style="' . $array [$res['font']] . '">' . $name . '</span>';
        $cms_ads[$res['type']] .= '<a href="' . $home . '/str/redirect.php?id=' . $res['id'] . '">' . $name . '</a><br/>';
        if (($res['day'] != 0 && $realtime >= ($res['time'] + $res['day'] * 3600 * 24)) || ($res['count_link'] != 0 && $res['count'] >= $res['count_link']))
            mysql_query("UPDATE `cms_ads` SET `to` = '1'  WHERE `id` = '" . $res['id'] . "'");
    }
}

// Рекламный блок сайта
if (!empty ($cms_ads[0]))
    echo $cms_ads[0];

// Выводим логотип
echo '<div><img src="' . $home . '/theme/' . $set_user['skin'] . '/images/logo.gif" alt=""/></div>';

// Выводим верхний блок с приветствием
echo '<div class="header">Привет ' . ($user_id ? '<b> ' . $login . '</b>!' : 'прохожий!') . '</div>';

// Выводим меню пользователя
echo '<div class="tmn">';
echo ($headmod != "mainpage" || ($headmod == 'mainpage' && $act)) ? '<a href=\'' . $home . '\'>На главную</a> | ' : '';
echo ($user_id && $mod != 'cab') ? '<a href="' . $home . '/index.php?act=cab">Личное</a> | ' : '';
echo $user_id ? '<a href="' . $home . '/exit.php">Выход</a>' : '<a href="' . $home . '/login.php">Вход</a> | <a href="' . $home . '/registration.php">Регистрация</a>';
echo '</div>';
echo '<div class="maintxt">';

// Рекламный блок сайта
if (!empty ($cms_ads[1]))
    echo '<div class="gmenu">' . $cms_ads[1] . '</div>';

////////////////////////////////////////////////////////////
// Фиксация местоположений посетителей                    //
////////////////////////////////////////////////////////////
$sql = '';
$set_karma = unserialize($set['karma']);
if ($user_id) {
	// Фиксируем местоположение авторизованных
    if(!$datauser['karma_off'] && $set_karma['on'] && $datauser['karma_time'] <= ($realtime-86400)) {
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
    if ($datauser['ip'] != $ipl)
        $sql .= "`ip` = '$ipl',";
    if ($datauser['browser'] != $agn)
        $sql .= "`browser` = '" . mysql_real_escape_string($agn) . "',";
    $totalonsite = $datauser['total_on_site'];
    if ($datauser['lastdate'] > ($realtime - 300))
        $totalonsite = $totalonsite + $realtime - $datauser['lastdate'];
    mysql_query("UPDATE `users` SET $sql
    `total_on_site` = '$totalonsite',
    `lastdate` = '$realtime'
    WHERE `id` = '$user_id'");
}
else {
    // Фиксируем местоположение гостей
    $sid = md5($ipl . $agn);
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
        if ($res['ip'] != $ipl)
            $sql .= "`ip` = '$ipl',";
        if ($res['browser'] != $agn)
            $sql .= "`browser` = '" . mysql_real_escape_string($agn) . "',";
        if ($res['place'] != $headmod) {
            $movings = $movings + 1;
            $sql .= "`movings` = '$movings', `place` = '$headmod',";
        }
        mysql_query("UPDATE `cms_guests` SET $sql
        `lastdate` = '$realtime'
        WHERE `session_id` = '$sid'");
    }
    else {
        // Если еще небыло в базе, то добавляем запись
        mysql_query("INSERT INTO `cms_guests` SET
        `session_id` = '$sid',
        `ip` = '$ipl',
        `browser` = '" . mysql_real_escape_string($agn) .
        "',
        `lastdate` = '$realtime',
        `sestime` = '$realtime',
        `place` = '$headmod'");
    }
}

// Выводим сообщение о Бане
if (isset ($ban))
    echo '<div class="alarm">БАН&nbsp;<a href="' . $home . '/str/users_ban.php">Подробно</a></div>';

// Проверяем, есть ли новые письма
if ($headmod != "pradd" && $user_id) {
    $countnew = mysql_result(mysql_query("SELECT COUNT(*) FROM `privat` WHERE `user` = '$login' AND `type` = 'in' AND `chit` = 'no'"), 0);
    if ($countnew > 0) {
        echo "<div class=\"rmenu\" style='text-align: center'><a href='$home/str/pradd.php?act=in&amp;new'><b><font color='red'>Вам письмо: $countnew</font></b></a></div>";
    }
}

?>