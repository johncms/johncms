<?php

defined('_IN_PUSTO') or die ('Error:restricted access');
if ($bann=="1"|$bann=="2"){$textl="Банннн!!!";}
##################
$ipban=mysql_query ("select * from `bann` WHERE `ip` = '".getenv(REMOTE_ADDR)."' and `browser` = '".getenv(HTTP_USER_AGENT)."';");
$ipb = mysql_fetch_array($ipban);
$ipadm = $ipb['admin'];
$ipb1=mysql_num_rows($ipban);
if($ipb1!=0){$textl="Банннн!!!(IP+Soft)";}
if ($gzip=="1"){ob_start('ob_gzhandler');}  
####################
if (!isset($headmod)) $headmod = '';
if ($headmod=="mainpage"){$textl=$copyright;}

if ($headmod!="auto"){
$agent = $_SERVER['HTTP_USER_AGENT'];

header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");  
header("Cache-Control: no-cache, must-revalidate"); 
header("Pragma: no-cache"); 
header("Last-Modified: ".gmdate("D, d M Y H:i:s")."GMT");

if (eregi("msie",$agent) && eregi("windows",$agent)){
header('Content-type: text/html; charset=UTF-8');}else{
header ('Content-type: application/xhtml+xml; charset=UTF-8');}

echo '<?xml version="1.0" encoding="utf-8"?>';
echo "\n".'<!DOCTYPE html PUBLIC "-//WAPFORUM//DTD XHTML Mobile 1.0//EN" ';
echo "\n".'"http://www.wapforum.org/DTD/xhtml-mobile10.dtd">';
echo "\n".'<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="ru">
<head>
<meta http-equiv="content-type" content="application/xhtml+xml; charset=utf-8"/>';




echo "<link rel='shortcut icon' href='favicon.ico' />
      <title>
      $textl
      </title>
<style type='text/css'>

body {
	font-family: verdana, arial, tahoma, sans-serif; 
	font-size: 12px;
	color: $colt;
	background: #4C4C4C;
	background-color: $fon;

	}

.a 	{
	background-color: $fon;
	border: 1px solid #c4c4c4;
	margin:2px 1px 4px 1px;
	padding:2px;
	}
.b 	{
	background-color: $clb;
	padding:2px;
	margin: 0px;
	}
.c 	{
	background-color: $clc;
	padding:2px;
	margin: 0px;
	}
.d {background-color: $fon;  text-align: left; font-size: 12px; color: $clink; }
.e {
	background-color: $clc;
	padding:2px;
	margin: 0px;
	}
a:link { text-decoration: underline; color : $clink}
a:active { text-decoration: underline; color : #666600 }
a:visited { text-decoration: underline; color : $clink}
a:hover { text-decoration: none; color : #FF6600 }
</style>
      </head>
      <body>";

echo "<div class = 'a' ><center><img src='".$home."/images/logo.gif' alt=''/></center></div>";
echo "<div class = 'a' ><center><b>$textl</b></center></div><div class = 'a' ><div class = 'e' ><center>";
$tvr=$realtime+$sdvig*3600;
$vrem=date("H:i / d.m.Y",$tvr);
if ($headmod=="mainpage"){
echo "$vrem<br/>";}
  if (!empty($_SESSION['pid']))
  {echo "Привет,<b> ".$login."</b>!<br/>";}else{
 echo "Привет, прохожий!<br/>";} 

  if (!empty($_SESSION['pid'])){
    if ($dostmod==1)
    {

      echo'<a href="'.$home.'/'.$admp.'/main.php">Админка</a>|';
    }  
echo "<a href='".$home."/str/anketa.php'>Анкета</a>|<a href='".$home."/str/usset.php'>Настройки</a>|<a href='".$home."/str/privat.php'>Приват</a>|<a href='".$home."/exit.php'>Выход</a><br/>"; } else{
echo "<a href='".$home."/in.php'>Вход</a>|<a href='".$home."/registration.php'>Регистрация</a>|<a href='".$home."/str/skl.php'>Забыл пароль</a><br/>";}
  

echo "</center></div></div><div class = 'a' >";
}

?>