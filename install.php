<?php

define('_IN_PUSTO', 1);
Error_Reporting(E_ALL & ~E_NOTICE);
Error_Reporting (ERROR | WARNING);


header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");  
header("Cache-Control: no-cache, must-revalidate"); 
header("Pragma: no-cache"); 
header("Last-Modified: ".gmdate("D, d M Y H:i:s")."GMT");
header ("Content-type: application/xhtml+xml; charset=UTF-8");
echo "<!DOCTYPE html PUBLIC '-//W3C//DTD XHTML 1.0 Strict//EN' 'http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd'>
<html xmlns='http://www.w3.org/1999/xhtml' xml:lang='en'>
<head>

<meta http-equiv='content-type' content='application/xhtml+xml; charset=utf-8'/>";
echo "<link rel='shortcut icon' href='ico.gif' />
      <title>
      УСТАНОВКА
      </title>
<style type='text/css'>

body { font-weight: normal; font-family: Century Gothic; font-size: 12px; color: #FFFFFF; background-color: #000033}
a:link { text-decoration: underline; color : #D3ECFF}
a:active { text-decoration: underline; color : #2F3528 }
a:visited { text-decoration: underline; color : #31F7D4}
a:hover { text-decoration: none; font-size: 12px; color : #E4F992 }
 

</style>
      </head><body>";



 
switch ($_GET['act']){
case "set":
require("incfiles/db.php");
echo"Создание таблиц<br/><br/>";


mysql_query("DROP TABLE IF EXISTS bann;");	
   $bnt = mysql_query("CREATE TABLE `bann` (
  `user` varchar(25) NOT NULL default '',
  `ip` varchar(20) NOT NULL default '',
  `browser` text NOT NULL default '',
  `admin` varchar(25) NOT NULL default '',
  `time` int(15) NOT NULL,
  `why` text NOT NULL default '',
  `kolv` char(3) NOT NULL default '',
  `type` char(1) NOT NULL default ''
) TYPE=MyISAM;");

if ($bnt){
echo"Бан-лист  ништяк<br/>";}else{
echo"Бан-лист  error!!!<br/>";}
mysql_query("DROP TABLE IF EXISTS themes;");
 $tht = mysql_query("CREATE TABLE `themes` (
`id` INT( 11 ) NOT NULL AUTO_INCREMENT ,
`name` VARCHAR( 25 ) NOT NULL default '',
`time` INT( 11 ) NOT NULL ,
`bgcolor` VARCHAR( 15 ) NOT NULL default '',
`tex` VARCHAR( 15 ) NOT NULL default '',
`link` VARCHAR( 15 ) NOT NULL default '',
`bclass` VARCHAR( 15 ) NOT NULL default '',
`cclass` VARCHAR( 15 ) NOT NULL default '',
`pfon` BINARY( 1 ) NOT NULL default '',
`cpfon` VARCHAR( 15 ) NOT NULL default '',
`ccfon` VARCHAR( 15 ) NOT NULL default '',
`cctx` VARCHAR( 15 ) NOT NULL default '',
`cntem` VARCHAR( 15 ) NOT NULL default '',
`ccolp` VARCHAR( 15 ) NOT NULL default '',
`cdtim` VARCHAR( 15 ) NOT NULL default '',
`cssip` VARCHAR( 15 ) NOT NULL default '',
`csnik` VARCHAR( 15 ) NOT NULL default '',
`conik` VARCHAR( 15 ) NOT NULL default '',
`cadms` VARCHAR( 15 ) NOT NULL default '',
`cons` VARCHAR( 15 ) NOT NULL default '',
`coffs` VARCHAR( 15 ) NOT NULL default '',
`cdinf` VARCHAR( 15 ) NOT NULL default '',
PRIMARY KEY ( `id` ) 
);");
if ($tht){
echo"Темы  ништяк<br/>";}else{
echo"Темы  error!!!<br/>";}


mysql_query("DROP TABLE IF EXISTS chat;");
   $ct = mysql_query("CREATE TABLE `chat` (
  `id` int(11) NOT NULL auto_increment,
  `refid` int(11) NOT NULL,
  `realid` int(2) NOT NULL,
  `type` char(3) NOT NULL default '',
  `time` int(15) NOT NULL,
  `from` varchar(25) NOT NULL default '',
  `to` varchar(15) NOT NULL default '',
  `dpar` char(3) NOT NULL default '',
  `text` text NOT NULL default '',
  `ip` text NOT NULL default '',
  `soft` text NOT NULL default '',
  `nas` text NOT NULL default '',
  `otv` int(1) NOT NULL,
  PRIMARY KEY  (`id`)
) TYPE=MyISAM AUTO_INCREMENT=1 ;");
if ($ct){
echo"Чат  ништяк<br/>";}else{
echo"Чат  error!!!<br/>";}
mysql_query("DROP TABLE IF EXISTS count;");
   $cct = mysql_query("CREATE TABLE `count` (
 `id` int(11) NOT NULL auto_increment,
  `ip` varchar(15) NOT NULL default '',
  `browser` text NOT NULL,
  `time` varchar(25) NOT NULL default '',
  `where` varchar(100) NOT NULL default '',
  `name` varchar(25) NOT NULL default '',
`dos` binary(1) NOT NULL default '',
  PRIMARY KEY  (`id`)
) TYPE=MyISAM AUTO_INCREMENT=1 ;");

if ($cct){
echo"Счётчик  ништяк<br/>";}else{
echo"Счётчик  error!!!<br/>";}
###############

mysql_query("DROP TABLE IF EXISTS download;");	
   $dlt = mysql_query("CREATE TABLE `download` (
`id` int(11) NOT NULL auto_increment,
  `refid` int(11) NOT NULL,
  `adres` text NOT NULL,
  `time` int(11) NOT NULL,
  `name` text NOT NULL default '',
  `type` varchar(4) NOT NULL default '',
`avtor` varchar(25) NOT NULL default '',
`ip` text NOT NULL default '',
`soft` text NOT NULL default '',
`text` text NOT NULL default '',
`screen` text NOT NULL default '',
  PRIMARY KEY  (`id`)
) TYPE=MyISAM AUTO_INCREMENT=1 ;");
if ($dlt){
echo"Загруз-центр  ништяк<br/>";}else{
echo"Загруз-центр  error!!!<br/>";}
mysql_query("DROP TABLE IF EXISTS upload;");
   $ult = mysql_query("CREATE TABLE `upload` (
`id` int(11) NOT NULL auto_increment,
  `refid` int(11) NOT NULL,
  `adres` text NOT NULL default '',
  `time` int(11) NOT NULL,
  `name` text NOT NULL default '',
  `type` varchar(4) NOT NULL default '',
`avtor` varchar(25) NOT NULL default '',
`ip` text NOT NULL default '',
`soft` text NOT NULL default '',
`text` text NOT NULL default '',
`screen` text NOT NULL default '',
`moder` binary(1) NOT NULL default '',
  PRIMARY KEY  (`id`)
) TYPE=MyISAM AUTO_INCREMENT=1 ;");
if ($ult){
echo"Обменник  ништяк<br/>";}else{
echo"Обменник  error!!!<br/>";}
mysql_query("DROP TABLE IF EXISTS forum;");
   $fmt = mysql_query("CREATE TABLE `forum` (
`id` int(11) NOT NULL auto_increment,
  `refid` int(11) NOT NULL,
  `type` char(1) NOT NULL default '',
  `time` int(11) NOT NULL,
  `from` varchar(25) NOT NULL default '',
`to` varchar(25) NOT NULL default '',
`realid` int(3) NOT NULL,
`ip` text NOT NULL default '',
`soft` text NOT NULL default '',
`text` text NOT NULL default '',
`close` binary(1) NOT NULL default '',
`vip` binary(1) NOT NULL default '',
`moder` binary(1) NOT NULL default '',
`edit` text NOT NULL default '',
`tedit` int(11) NOT NULL,
`kedit` int(2) NOT NULL,
`attach` text NOT NULL default '',
  PRIMARY KEY  (`id`)
) TYPE=MyISAM AUTO_INCREMENT=1 ;");
if ($fmt){
echo"Форум  ништяк<br/>";}else{
echo"Форум  error!!!<br/>";}

mysql_query("DROP TABLE IF EXISTS gallery;");
   $glt = mysql_query("CREATE TABLE `gallery` (
`id` int(11) NOT NULL auto_increment,
  `refid` int(11) NOT NULL,
  `time` int(11) NOT NULL,
  `type` char(2) NOT NULL default '',
  `avtor` varchar(25) NOT NULL default '',
`text` text NOT NULL default '',
`name` text NOT NULL default '',
`user` binary(1) NOT NULL default '',
`ip` text NOT NULL default '',
`soft` text NOT NULL default '',
  PRIMARY KEY  (`id`)
) TYPE=MyISAM AUTO_INCREMENT=1 ;");
if ($glt){
echo"Галерея  ништяк<br/>";}else{
echo"Галерея  error!!!<br/>";}


mysql_query("DROP TABLE IF EXISTS lib;");
   $lbt = mysql_query("CREATE TABLE `lib` (
`id` int(11) NOT NULL auto_increment,
  `refid` int(11) NOT NULL,
  `time` int(11) NOT NULL,
  `type` varchar(4) NOT NULL default '',
`name` varchar(50) NOT NULL default '',
  `avtor` varchar(25) NOT NULL default '',
`text` text NOT NULL default '',
`ip` text NOT NULL default '',
`soft` text NOT NULL default '',
`moder` binary(1) NOT NULL default '',
  PRIMARY KEY  (`id`)
) TYPE=MyISAM AUTO_INCREMENT=1 ;");
if ($lbt){
echo"Библиотека  ништяк<br/>";}else{
echo"Библиотека  error!!!<br/>";}
mysql_query("DROP TABLE IF EXISTS moder;");
   $mdt = mysql_query("CREATE TABLE `moder` (
`id` int(11) NOT NULL auto_increment,
  `time` int(11) NOT NULL,
  `to` varchar(25) NOT NULL default '',
  `avtor` varchar(25) NOT NULL default '',
`text` text NOT NULL default '',
`ip` text NOT NULL default '',
`soft` text NOT NULL default '',
  PRIMARY KEY  (`id`)
) TYPE=MyISAM AUTO_INCREMENT=1 ;");
if ($mdt){
echo"Модерка  ништяк<br/>";}else{
echo"Модерка  error!!!<br/>";}
mysql_query("DROP TABLE IF EXISTS news;");
   $nwt = mysql_query("CREATE TABLE `news` (
`id` int(11) NOT NULL auto_increment,
  `time` int(11) NOT NULL,
  `avt` varchar(25) NOT NULL default '',
  `name` text NOT NULL default '',
`text` text NOT NULL default '',
`kom` int(11) NOT NULL,
   PRIMARY KEY  (`id`)
) TYPE=MyISAM AUTO_INCREMENT=1 ;");
if ($nwt){
echo"Новости  ништяк<br/>";}else{
echo"Новости  error!!!<br/>";}

#####################
mysql_query("DROP TABLE IF EXISTS privat;");
   $ptt = mysql_query("CREATE TABLE `privat` (
  `id` int(11) NOT NULL auto_increment,
  `user` varchar(25) NOT NULL default '',
  `text` text NOT NULL,
  `time` varchar(25) NOT NULL default '',
  `author` varchar(25) NOT NULL default '',
  `type` char(3) NOT NULL default '',
  `chit` char(3) NOT NULL default '',
`temka` text NOT NULL default '',
`otvet` binary(1) NOT NULL default '',
`me` varchar(25) NOT NULL default '',
`cont` varchar(25) NOT NULL default '',
`ignor` varchar(25) NOT NULL default '',
`attach` text NOT NULL default '',
  UNIQUE KEY `id` (`id`)
) TYPE=MyISAM AUTO_INCREMENT=1 ;");
if ($ptt){
echo"Приват  ништяк<br/>";}else{
echo"Приват  error!!!<br/>";}

mysql_query("DROP TABLE IF EXISTS guest;");
$gbt=mysql_query("CREATE TABLE `guest` (
`id` INT( 11 ) NOT NULL AUTO_INCREMENT ,
`time` INT( 15 ) NOT NULL ,
`name` VARCHAR( 25 ) NOT NULL ,
`text` TEXT NOT NULL ,
`ip` TEXT NOT NULL ,
`soft` TEXT NOT NULL ,
`gost` BINARY( 1 ) NOT NULL ,
`admin` VARCHAR( 25 ) NOT NULL ,
`otvet` TEXT NOT NULL ,
`otime` INT( 15 ) NOT NULL ,
PRIMARY KEY ( `id` ) 
);");

if ($gbt){
echo"Гостевая  ништяк<br/>";}else{
echo"Гостевая  error!!!<br/>";}


mysql_query("DROP TABLE IF EXISTS vik;");
   $vct = mysql_query("CREATE TABLE `vik` (
`id` INT( 11 ) NOT NULL AUTO_INCREMENT ,
`vopros` TEXT NOT NULL ,
`otvet` TEXT NOT NULL ,
PRIMARY KEY ( `id` ) 
);");

if ($vct){
echo"Викторина  ништяк<br/>";}else{
echo"Викторина  error!!!<br/>";}
$file=file("vopros.txt");
$count=count($file);
for ($i=0; $i<$count; $i++){
$tx=explode("||",$file[$i]);
mysql_query("INSERT INTO `vik` VALUES(
'0', '".trim($tx[0])."', '".trim($tx[1])."');");}
echo"Вопросов: $i <br/>";
mysql_query("DROP TABLE IF EXISTS settings;");
   $stt = mysql_query("CREATE TABLE `settings` (
  `id` int(11) NOT NULL auto_increment,
  `nickadmina` varchar(25) NOT NULL default '',
  `emailadmina` varchar(40) NOT NULL default '',
  `nickadmina2` varchar(25) NOT NULL default '',
  `sdvigclock` char(2) NOT NULL default '',
  `copyright` varchar(100) NOT NULL default '',
  `homeurl` varchar(150) NOT NULL default '',
  `rashstr` varchar(10) NOT NULL default '',
  `gzip` char(2) NOT NULL default '',
  `admp` varchar(25) NOT NULL default '',
  `rmod` binary(1) NOT NULL default '',
  `fmod` binary(1) NOT NULL default '',
  `flsz` int(4) NOT NULL,
  `gb` binary(1) NOT NULL default '',
  PRIMARY KEY  (`id`)
) TYPE=MyISAM AUTO_INCREMENT=1 ;");
if ($stt){
echo"Настройки  ништяк<br/>";}else{
echo"Настройки  error!!!<br/>";}
mysql_query("DROP TABLE IF EXISTS users;");
   $ust = mysql_query("CREATE TABLE `users` (
  `id` int(11) NOT NULL auto_increment,
  `name` varchar(25) NOT NULL,
  `password` varchar(32) NOT NULL,
  `imname` varchar(25) NOT NULL default '',
  `sex` char(2) NOT NULL default '',
  `komm` int(10) NOT NULL,
  `postforum` int(10) NOT NULL,
  `postchat` int(10) NOT NULL,
  `otvetov` int(11) NOT NULL,
  `yearofbirth` int(4) NOT NULL,
  `datereg` int(11) NOT NULL,
  `lastdate` int(11) NOT NULL,
  `mail` varchar(50) NOT NULL default '',
  `icq` int(9) NOT NULL,
  `www` varchar(50) NOT NULL default '',
  `about` text NOT NULL,
  `live` varchar(50) NOT NULL default '',
  `mibile` varchar(50) NOT NULL default '',
  `rights` int(1) NOT NULL,
  `status` text NOT NULL default '',
  `ip` varchar(25) NOT NULL default '',
  `browser` text NOT NULL,
  `timererfesh` int(2) NOT NULL,
  `kolanywhwere` int(2) NOT NULL,
`bgcolor` varchar(15) NOT NULL default '',
`tex` varchar(15) NOT NULL default '',
`link` varchar(15) NOT NULL default '',
`bclass` varchar(15) NOT NULL default '',
`cclass` varchar(15) NOT NULL default '',
`ban` int(1) NOT NULL,
`why` text NOT NULL default '',
`who` varchar(25) NOT NULL default '',
`bantime` int(15) NOT NULL,
`time` int(11) NOT NULL,
`preg` binary(1) NOT NULL default '',
`regadm` varchar(25) NOT NULL default '',
`kod` int(15) NOT NULL,
`mailact` binary(1) NOT NULL default '',
`mailvis` binary(1) NOT NULL default '',
`vremja` int(15) NOT NULL,
`sdvig` int(2) NOT NULL,
`dayb` int(2) NOT NULL,
`monthb` int(2) NOT NULL,
`fban` binary(1) NOT NULL default '',
`fwhy` text NOT NULL default '',
`fwho` varchar(25) NOT NULL default '',
`ftime` int(15) NOT NULL,
`chban` binary(1) NOT NULL default '',
`chwhy` text NOT NULL default '',
`chwho` varchar(25) NOT NULL default '',
`chtime` int(15) NOT NULL,
`offpg` binary(1) NOT NULL default '',
`offgr` binary(1) NOT NULL default '',
`offsm` binary(1) NOT NULL default '',
`offtr` int(15) NOT NULL,
`nastroy` text NOT NULL default '',
`plus` int(3) NOT NULL,
`minus` int(3) NOT NULL,
`vrrat` int(11) NOT NULL,
`pfon` binary(1) NOT NULL default '',
`cpfon` varchar(15) NOT NULL default '',
`ccfon` varchar(15) NOT NULL default '',
`cctx` varchar(15) NOT NULL default '',
`cntem` varchar(15) NOT NULL default '',
`ccolp` varchar(15) NOT NULL default '',
`cdtim` varchar(15) NOT NULL default '',
`cssip` varchar(15) NOT NULL default '',
`csnik` varchar(15) NOT NULL default '',
`conik` varchar(15) NOT NULL default '',
`cadms` varchar(15) NOT NULL default '',
`cons` varchar(15) NOT NULL default '',
`coffs` varchar(15) NOT NULL default '',
`cdinf` varchar(15) NOT NULL default '',
`upfp` binary(1) NOT NULL default '',
`farea` binary(1) NOT NULL default '',
`chmes` int(2) NOT NULL,
`nmenu` text NOT NULL default '',
`carea` binary(1) NOT NULL default '',
`alls` varchar(25) NOT NULL default '',
`pereh` binary(1) NOT NULL default '',
`balans` int(11) NOT NULL,
`sestime` int(15) NOT NULL,
  PRIMARY KEY  (`id`)
) TYPE=MyISAM AUTO_INCREMENT=1 ;");
if ($ust){
echo"Юзеры  ништяк<br/>";}else{
echo"Юзеры  error!!!<br/>";}



$log=$_POST['wnickadmina'];
$par=$_POST['wpassadmina'];
$par1=md5(md5($par));
$tim=time();
$meil=$_POST['wemailadmina'];
$hom=$_POST[whome];
$brow=getenv(HTTP_USER_AGENT);
$ip=getenv(REMOTE_ADDR);
$cop=$_POST['wcopyright'];


mysql_query("insert into `users` values(0,'".$log."','".$par1."','','m','0','0','0','0','','".$tim."','".$tim."','".$meil."','0','".$hom."','','','','7','','".$ip."','".$brow."','20','20','','','','','','0','','','','','1','','','1','1','0','0','','','0','','','0','0','','','','0','0','0','0','','0','0','0','0','','','','','','','','','','','','','','0','0','15','','0','','','','');");
###
mysql_query("insert into `settings` values(0,'".$log."','".$meil."','','0','".$cop."','".$hom."','txt','0','panel','0','0','300','0');");



echo "<br/>Рекомендую сменить права на папке incfiles на 755,на файл incfiles/db.php 644,и удалить install.php с сайта.<br/><a href='auto.php?n=".$_POST['wnickadmina']."&amp;p=".$_POST['wpassadmina']."'>Вход!!!</a><br/>"; 
break;
case "install":
Error_Reporting(E_ALL & ~E_NOTICE);
Error_Reporting (ERROR | WARNING);
session_name("SESID");
session_start();
$dhost=$_POST['host'];
$duser=$_POST['user'];
$dpass=$_POST['pass'];
$dname=$_POST['name'];


$text="<?php\r\n
"."defined('_IN_PUSTO') or die ('Error:restricted access');\r\n".
"Error_Reporting(E_ALL & ~E_NOTICE);\r\n".
"Error_Reporting (ERROR | WARNING);\r\n".
"session_name(\"SESID\");\r\n".
"session_start();\r\n".
 
"$"."db_host=\"$dhost\";\r\n". 
"$"."db_user=\"$duser\";\r\n".
"$"."db_pass=\"$dpass\";\r\n".
"$"."db_name=\"$dname\";\r\n".
"$"."connect=mysql_connect("."$"."db_host, "."$"."db_user, "."$"."db_pass) or die ('cannot connect to server');\r\n".
"mysql_select_db("."$"."db_name) or die ('cannot connect to db');\r\n".
"?>";
$fp=@fopen("incfiles/db.php","w");
fputs($fp, $text);
fclose($fp);










echo"Установка сайта<br/><form method='post' action='install.php?act=set'>Ник админа:<br/><input name='wnickadmina' maxlength='50' /><br/>Пароль админа:<br/><input name='wpassadmina' maxlength='50' /><br/>е-mail админа:<br/><input name='wemailadmina' maxlength='50' /><br/>Копирайт:<br/><input name='wcopyright' maxlength='100' /><br/>Главная сайта без слэша в конце:<br/><input name='whome' maxlength='100' /><br/><br/><input value='УСТАНОВИТЬ' type='submit'/></form>";

break;
default:
Error_Reporting(E_ALL & ~E_NOTICE);
Error_Reporting (ERROR | WARNING);
session_name("SESID");
session_start();
function permissions($filez){
$filez = @decoct(@fileperms("$filez")) % 1000;
return $filez;}
$cherr="";
$arr=array("incfiles","gallery/foto","gallery/temp","str/temp","pratt","forum/files","forum/temtemp","download/arctemp","download/files","download/graftemp","download/screen","download/mp3temp","download/upl");
foreach($arr as $v){
if(permissions($v)<777){$cherr=$cherr."$v<br/>";}}

if (empty($cherr)){
echo "<center><b>Настройки соединения</b></center><br/><br/><form action='install.php?act=install&amp;' method='post'><div style='background:#003300;color:#CCCCCC;'>Сервер<br/><input type='text' name='host' value='localhost'/><br/>Имя пользователя<br/><input type='text' name='user' value=''/><br/>Пароль<br/><input type='password' name='pass' /><br/>Название базы<br/><input type='text' name='name' value=''/><br/><br/><input type='submit' value='Ok!'/><br/></div><br/></form>";}else{
echo "<div style='background:#000066;color:#00FF00;'>Вы не можете приступить к установке портала,поскольку не выставлены необходимые права (777) на следующие папки:<br/><br/>$cherr</div>";}



break;}

echo "</body></html>";
?>