<?php
/*
////////////////////////////////////////////////////////////////////////////////
// JohnCMS v.1.0.0 RC1                                                        //
// Дата релиза: 08.02.2008                                                    //
// Авторский сайт: http://gazenwagen.com                                      //
////////////////////////////////////////////////////////////////////////////////
// Оригинальная идея и код: Евгений Рябинин aka JOHN77                        //
// E-mail: 
// Модификация, оптимизация и дизайн: Олег Касьянов aka AlkatraZ              //
// E-mail: alkatraz@batumi.biz                                                //
// Плагиат и удаление копирайтов заруганы на ближайших родственников!!!       //
////////////////////////////////////////////////////////////////////////////////
// Внимание!                                                                  //
// Авторские версии данных скриптов публикуются ИСКЛЮЧИТЕЛЬНО на сайте        //
// http://gazenwagen.com                                                      //
// Если Вы скачали данный скрипт с другого сайта, то его работа не            //
// гарантируется и поддержка не оказывается.                                  //
////////////////////////////////////////////////////////////////////////////////
*/

defined('_IN_PUSTO') or die('Error:restricted access');
if ($bann == "1" | $bann == "2")
{
    $textl = "Банннн!!!";
}
$ipban = mysql_query("select * from `bann` WHERE `ip` = '" . getenv(REMOTE_ADDR) . "' and `browser` = '" . getenv(HTTP_USER_AGENT) . "';");
$ipb = mysql_fetch_array($ipban);
$ipadm = $ipb['admin'];
$ipb1 = mysql_num_rows($ipban);
if ($ipb1 != 0)
{
    $textl = "Банннн!!!(IP+Soft)";
}
if ($gzip == "1")
{
    ob_start('ob_gzhandler');
}

if (!isset($headmod))
    $headmod = '';
if ($headmod == "mainpage")
{
    $textl = $copyright;
}

if ($headmod != "auto")
{
    $agent = $_SERVER['HTTP_USER_AGENT'];
    header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");
    header("Cache-Control: no-cache, must-revalidate");
    header("Pragma: no-cache");
    header("Last-Modified: " . gmdate("D, d M Y H:i:s") . "GMT");
    if (eregi("msie", $agent) && eregi("windows", $agent))
    {
        header('Content-type: text/html; charset=UTF-8');
    } else
    {
        header('Content-type: application/xhtml+xml; charset=UTF-8');
    }
    echo '<?xml version="1.0" encoding="utf-8"?>' . "\n";
    echo '<!DOCTYPE html PUBLIC "-//WAPFORUM//DTD XHTML Mobile 1.0//EN" ' . "\n";
    echo '"http://www.wapforum.org/DTD/xhtml-mobile10.dtd">' . "\n";
    echo '<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="ru">' . "\n";
    echo '<head><meta http-equiv="content-type" content="application/xhtml+xml; charset=utf-8"/>';
    echo '<link rel="shortcut icon" href="favicon.ico" />';
    echo '<title>' . $textl . '</title>';
    echo "<style type='text/css'>
body { background-color: #ffffff;  color: black;  font-family: Arial, Tahoma, sans-serif;  font-size: 8pt;  margin: 0px;  border: 0px;  padding: 0px;}
form {padding: 0px; margin: 0px; font-size: small;}
.header  {
	background-color: #586776;
	color: #FFFFFF;
	font-size: 18px;
	font-weight: bold;
	font-family: Arial, Helvetica, sans-serif;
	padding-top: 5px;
	padding-right: 0px;
	padding-bottom: 5px;
	padding-left: 2px;
	margin: 0px;
}
.maintxt {
	font-weight: normal;
	font-size: 8pt;
	PADDING-RIGHT: 2px;
	PADDING-LEFT: 2px;
	PADDING-BOTTOM: 0px;
	MARGIN: 0px;
	PADDING-TOP: 0px;
}
.ackey {
	text-decoration: underline;
	font-size: 11px;
}
.topmenu {
	color: #003300;
	font-size: 11px;
	font-weight: normal;
	padding: 1px 0px 2px 3px;
	font-family: Arial, Helvetica, sans-serif;
	background-color: #FFFFFF;
	margin: 0px;
	border-top: 1px solid #000000;
	border-bottom: 1px solid #586776;
}
.menu {
	background-color: #d6dce2;
	margin: 0px;
	border-top-width: 1px;
	border-right-width: 0px;
	border-bottom-width: 1px;
	border-left-width: 0px;
	border-top-style: solid;
	border-right-style: solid;
	border-bottom-style: solid;
	border-left-style: solid;
	border-top-color: #FFFFFF;
	border-bottom-color: #CCCCCC;
}
.fmenu {
	color: #003300;
	font-size: 11px;
	font-weight: normal;
	font-family: Arial, Helvetica, sans-serif;
	padding-top: 1px;
	padding-right: 1px;
	padding-bottom: 2px;
	padding-left: 3px;
	margin: 0px;
	border-top-width: 1px;
	border-right-width: 0px;
	border-bottom-width: 1px;
	border-left-width: 0px;
	border-top-style: solid;
	border-right-style: solid;
	border-bottom-style: solid;
	border-left-style: solid;
	border-top-color: #586776;
	border-right-color: #003300;
	border-bottom-color: #003300;
	border-left-color: #003300;
}
.footer  {
	color: #FFFFFF;
	background-color: #586776;
	font-family: Arial, Helvetica, sans-serif;
	font-size: 11px;
	font-weight: normal;
	padding-top: 2px;
	padding-right: 0;
	padding-bottom: 3px;
	padding-left: 3px;
	margin: 0px;
}
.footer a:link, .footer a:visited {
	color: #FFFFFF;
}

a:link, a:visited {
	text-decoration: underline;
	color: $clink;
}

a:hover {
	text-decoration: none;
	color: #ff6600;
}

a:active {
	text-decoration: underline;
	color: #666600;
}

body {
	color: $colt;
	background-color: $fon;
}
hr{
	margin: 0;
	border: 0;
	border-top: 1px solid #586776;
}
.a 	{
	background-color: $fon;
	border: 1px solid #b5bec7;
	margin-bottom: 3px;
	padding: 2px;
}

.b 	{
	background-color: $clb;
	padding: 2px;
	margin: 0px;
}

.c, .e {
	background-color: $clc;
	padding: 2px;
	margin: 0px;
}

.d {
	background-color: $fon;
	text-align: left;
	font-size: 12px;
	color: $clink;
}

.end{
	text-align: center;
	color: #000000;
}

.hdr{
	font-weight: bold;
	border-bottom: 1px dotted #0000ff;
	padding-left: 2px;
	background-color: #f1f1f1;
}
</style>
      </head>
      <body>";

    // Выводим логотип. Если нужно, то раскомментируйте строку ниже
    //echo '<div><center><img src="' . $home . '/images/logo.gif" alt=""/></center></div>';

    // Выводим название сайта
    echo '<div class="header">' . $textl . '</div>';

    // Выводим меню пользователя
    echo '<div class="topmenu">';
    $tvr = $realtime + $sdvig * 3600;
    $vrem = date("H:i / d.m.Y", $tvr);

    // Выводим текущее время. Если нужно, то раскомментируйте
    //if ($headmod == "mainpage")
    //{
    //    echo $vrem . '<br/>';
    //}

    // Выводим приветствие
    //if (!empty($_SESSION['pid']))
    //{
    //    echo "Привет,<b> " . $login . "</b>!<br/>";
    //} else
    //{
    //    echo "Привет, прохожий!<br/>";
    //}

    // Выводим меню пользователя вверху сайта
    if ($headmod != "mainpage" || isset($_GET['do']))
    {
        echo '<a href=\'' . $home . '\'>На главную</a> | ';
    }
    if (!empty($_SESSION['pid']))
    {
        echo "<a href='" . $home . "/index.php?do=cab'>Личное</a> | <a href='" . $home . "/exit.php'>Выход</a><br/>";
    } else
    {
        echo "<a href='" . $home . "/in.php'>Вход</a> | <a href='" . $home . "/registration.php'>Регистрация</a><br/>";
    }
    echo '</div><div class="maintxt">';
}

?>