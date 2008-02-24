<?php
/*
////////////////////////////////////////////////////////////////////////////////
// JohnCMS v.1.0.0 RC2                                                        //
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

define('_IN_PUSTO', 1);
Error_Reporting(E_ALL & ~ E_NOTICE);
Error_Reporting(ERROR | WARNING);
session_name("SESID");
session_start();
header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");
header("Cache-Control: no-cache, must-revalidate");
header("Pragma: no-cache");
header("Last-Modified: " . gmdate("D, d M Y H:i:s") . "GMT");
header("Content-type: application/xhtml+xml; charset=UTF-8");
echo "<!DOCTYPE html PUBLIC '-//W3C//DTD XHTML 1.0 Strict//EN' 'http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd'>
<html xmlns='http://www.w3.org/1999/xhtml' xml:lang='en'>
<head>
<meta http-equiv='content-type' content='application/xhtml+xml; charset=utf-8'/>";
echo "<link rel='shortcut icon' href='ico.gif' />
<title>Установка системы</title>
<style type='text/css'>
body { font-weight: normal; font-family: Century Gothic; font-size: 12px; color: #FFFFFF; background-color: #000033}
a:link { text-decoration: underline; color : #D3ECFF}
a:active { text-decoration: underline; color : #2F3528 }
a:visited { text-decoration: underline; color : #31F7D4}
a:hover { text-decoration: none; font-size: 12px; color : #E4F992 }
.red { color: #FF0000; font-weight: bold; }
.green{ color: #009933; font-weight: bold; }
.gray{ color: #FF0000; font: small; }
</style>
</head><body>";
switch ($_GET['act'])
{
    case "set":
        ////////////////////////////////////////////////////////////
        // Создание таблиц в базе данных MySQL                    //
        ////////////////////////////////////////////////////////////
        require ("../incfiles/db.php");
        echo 'ЗАВЕРШНИЕ УСТАНОВКИ<hr />';
        echo "<b>Создание таблиц</b><br/>";
        mysql_query("DROP TABLE IF EXISTS bann;");
        mysql_query("CREATE TABLE `bann` (
`id` INT( 11 ) NOT NULL AUTO_INCREMENT ,
`user` varchar(25) NOT NULL default '',
`ip` varchar(20) NOT NULL default '',
`browser` text NOT NULL default '',
`admin` varchar(25) NOT NULL default '',
`time` int(15) NOT NULL,
`why` text NOT NULL default '',
`kolv` char(3) NOT NULL default '',
`type` char(1) NOT NULL default '',
PRIMARY KEY ( `id` )
) TYPE=MyISAM DEFAULT CHARSET=utf8;") or die('Ошибка создания таблицы "bann"</body></html>');
        echo '<span class="green">OK</span> - Бан-лист<br/>';

        mysql_query("DROP TABLE IF EXISTS themes;");
        mysql_query("CREATE TABLE `themes` (
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
) TYPE=MyISAM DEFAULT CHARSET=utf8;") or die('Ошибка создания таблицы "themes"</body></html>');
        echo '<span class="green">OK</span> - Темы<br/>';

        mysql_query("DROP TABLE IF EXISTS chat;");
        mysql_query("CREATE TABLE `chat` (
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
) TYPE=MyISAM DEFAULT CHARSET=utf8;") or die('Ошибка создания таблицы "chat"</body></html>');
        echo '<span class="green">OK</span> - Чат<br/>';

        mysql_query("DROP TABLE IF EXISTS count;");
        mysql_query("CREATE TABLE `count` (
`id` int(11) NOT NULL auto_increment,
`ip` varchar(15) NOT NULL default '',
`browser` text NOT NULL,
`time` varchar(25) NOT NULL default '',
`where` varchar(100) NOT NULL default '',
`name` varchar(25) NOT NULL default '',
`dos` binary(1) NOT NULL default '',
PRIMARY KEY  (`id`)
) TYPE=MyISAM DEFAULT CHARSET=utf8;") or die('Ошибка создания таблицы "count"</body></html>');
        echo '<span class="green">OK</span> - Счётчик<br/>';

        mysql_query("DROP TABLE IF EXISTS download;");
        mysql_query("CREATE TABLE `download` (
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
) TYPE=MyISAM DEFAULT CHARSET=utf8;") or die('Ошибка создания таблицы "download"</body></html>');
        echo '<span class="green">OK</span> - Загруз-центр<br/>';

        mysql_query("DROP TABLE IF EXISTS upload;");
        mysql_query("CREATE TABLE `upload` (
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
) TYPE=MyISAM DEFAULT CHARSET=utf8;") or die('Ошибка создания таблицы "upload"</body></html>');
        echo '<span class="green">OK</span> - Обменник<br/>';

        mysql_query("DROP TABLE IF EXISTS forum;");
        mysql_query("CREATE TABLE `forum` (
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
PRIMARY KEY  (`id`),
KEY `from` (`from`),
KEY `refid` (`refid`)
) TYPE=MyISAM DEFAULT CHARSET=utf8;") or die('Ошибка создания таблицы "forum"</body></html>');
        echo '<span class="green">OK</span> - Форум<br/>';

        mysql_query("DROP TABLE IF EXISTS gallery;");
        mysql_query("CREATE TABLE `gallery` (
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
) TYPE=MyISAM DEFAULT CHARSET=utf8;") or die('Ошибка создания таблицы "gallery"</body></html>');
        echo '<span class="green">OK</span> - Галерея<br/>';

        mysql_query("DROP TABLE IF EXISTS lib;");
        mysql_query("CREATE TABLE `lib` (
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
) TYPE=MyISAM DEFAULT CHARSET=utf8;") or die('Ошибка создания таблицы "lib"</body></html>');
        echo '<span class="green">OK</span> - Библиотека<br/>';

        mysql_query("DROP TABLE IF EXISTS moder;");
        mysql_query("CREATE TABLE `moder` (
`id` int(11) NOT NULL auto_increment,
`time` int(11) NOT NULL,
`to` varchar(25) NOT NULL default '',
`avtor` varchar(25) NOT NULL default '',
`text` text NOT NULL default '',
`ip` text NOT NULL default '',
`soft` text NOT NULL default '',
PRIMARY KEY  (`id`)
) TYPE=MyISAM DEFAULT CHARSET=utf8;") or die('Ошибка создания таблицы "moder"</body></html>');
        echo '<span class="green">OK</span> - Модерка<br/>';

        mysql_query("DROP TABLE IF EXISTS news;");
        mysql_query("CREATE TABLE `news` (
`id` int(11) NOT NULL auto_increment,
`time` int(11) NOT NULL,
`avt` varchar(25) NOT NULL default '',
`name` text NOT NULL default '',
`text` text NOT NULL default '',
`kom` int(11) NOT NULL,
PRIMARY KEY  (`id`)
) TYPE=MyISAM DEFAULT CHARSET=utf8;") or die('Ошибка создания таблицы "news"</body></html>');
        echo '<span class="green">OK</span> - Новости<br/>';

        mysql_query("DROP TABLE IF EXISTS privat;");
        mysql_query("CREATE TABLE `privat` (
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
PRIMARY KEY  (`id`)
) TYPE=MyISAM DEFAULT CHARSET=utf8;") or die('Ошибка создания таблицы "privat"</body></html>');
        echo '<span class="green">OK</span> - Приват<br/>';

        mysql_query("DROP TABLE IF EXISTS guest;");
        mysql_query("CREATE TABLE `guest` (
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
) TYPE=MyISAM DEFAULT CHARSET=utf8;") or die('Ошибка создания таблицы "guest"</body></html>');
        echo '<span class="green">OK</span> - Гостевая<br/>';

        mysql_query("DROP TABLE IF EXISTS vik;");
        mysql_query("CREATE TABLE `vik` (
`id` INT( 11 ) NOT NULL AUTO_INCREMENT ,
`vopros` TEXT NOT NULL ,
`otvet` TEXT NOT NULL ,
PRIMARY KEY ( `id` )
) TYPE=MyISAM DEFAULT CHARSET=utf8;") or die('Ошибка создания таблицы "vik"</body></html>');
        echo '<span class="green">OK</span> - Викторина<br/>';

        // Импорт вопросов Викторины
        $file = file("vopros.txt");
        $count = count($file);
        for ($i = 0; $i < $count; $i++)
        {
            $tx = explode("||", $file[$i]);
            mysql_query("INSERT INTO `vik` VALUES('0', '" . mysql_real_escape_string(trim($tx[0])) . "', '" . mysql_real_escape_string(trim($tx[1])) . "');");
        }
        echo "Вопросов: $i <br/>";

        mysql_query("DROP TABLE IF EXISTS settings;");
        mysql_query("CREATE TABLE `settings` (
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
) TYPE=MyISAM DEFAULT CHARSET=utf8;") or die('Ошибка создания таблицы "settings"</body></html>');
        echo '<span class="green">OK</span> - Настройки<br/>';

        mysql_query("DROP TABLE IF EXISTS users;");
        mysql_query("CREATE TABLE `users` (
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
) TYPE=MyISAM DEFAULT CHARSET=utf8;")or die('Ошибка создания таблицы "users"</body></html>');
        echo '<span class="green">OK</span> - Юзеры<br/>';

        $log = trim($_POST['wnickadmina']);
        $par = trim($_POST['wpassadmina']);
        $par1 = md5(md5($par));
        $tim = time();
        $meil = trim($_POST['wemailadmina']);
        $hom = trim($_POST[whome]);
        $brow = $_SERVER["HTTP_USER_AGENT"];
        $ip = $_SERVER["REMOTE_ADDR"];
        $cop = trim($_POST['wcopyright']);
        mysql_query("insert into `users` values(0,'" . $log . "','" . $par1 . "','','m','0','0','0','0','','" . $tim . "','" . $tim . "','" . $meil . "','0','" . $hom . "','','','','7','','" . $ip . "','" . $brow .
            "','20','20','','','','','','0','','','','','1','','','1','1','0','0','','','0','','','0','0','','','','0','0','0','0','','0','0','0','0','','','','','','','','','','','','','','0','0','15','','0','','','','');");
        mysql_query("insert into `settings` values(0,'" . $log . "','" . $meil . "','','0','" . $cop . "','" . $hom . "','txt','0','panel','0','0','300','0');");
        echo "<hr/>Необходимо:<br />1) Сменить права к папке incfiles на 755<br />2) Сменить права на файл incfiles/db.php 644<br />3) Удалить папку install с сайта.<br/>";
        echo "<a href='../auto.php?n=" . $_POST['wnickadmina'] . "&amp;p=" . $_POST['wpassadmina'] . "'>Вход!!!</a><br/>";
        break;

    case "admin":
        $dhost = trim($_POST['host']);
        $duser = trim($_POST['user']);
        $dpass = trim($_POST['pass']);
        $dname = trim($_POST['name']);
        $text = "<?php\r\n
" . "defined('_IN_PUSTO') or die ('Error:restricted access');\r\n" . "Error_Reporting(E_ALL & ~E_NOTICE);\r\n" . "Error_Reporting (ERROR | WARNING);\r\n" . "session_name(\"SESID\");\r\n" . "session_start();\r\n" . "$" . "db_host=\"$dhost\";\r\n" .
            "$" . "db_user=\"$duser\";\r\n" . "$" . "db_pass=\"$dpass\";\r\n" . "$" . "db_name=\"$dname\";\r\n" . "$" . "connect=mysql_connect(" . "$" . "db_host, " . "$" . "db_user, " . "$" . "db_pass) or die ('cannot connect to server');\r\n" .
            "mysql_select_db(" . "$" . "db_name) or die ('cannot connect to db');\r\n" . "mysql_query( \"SET NAMES 'utf8'\", " . "$" . "connect );\r\n" . "?>";
        $fp = @fopen("../incfiles/db.php", "w");
        fputs($fp, $text);
        fclose($fp);
        echo 'Установка сайта<br/>';
        echo '<form method="post" action="index.php?act=set">';
        echo 'Ник админа:<br/><input name="wnickadmina" maxlength="50" /><br/>';
        echo 'Пароль админа:<br/><input name="wpassadmina" maxlength="50" /><br/>';
        echo 'е-mail админа:<br/><input name="wemailadmina" maxlength="50" /><br/>';
        echo 'Копирайт:<br/><input name="wcopyright" maxlength="100" /><br/>';
        echo 'Главная сайта без слэша в конце:<br/><input name="whome" maxlength="100" value="http://' . $_SERVER["SERVER_NAME"] . '" /><br/><br/>';
        echo '<input value="Установить" type="submit"/></form>';
        break;

    case 'db':
        ////////////////////////////////////////////////////////////
        // Настройка соединения с MySQL                           //
        ////////////////////////////////////////////////////////////
        echo '<center><b>Настройки соединения</b></center><br/><br/>';
        echo '<form action="index.php?act=admin&amp;" method="post">';
        echo '<div style="background:#003300;color:#CCCCCC;">';
        echo 'Сервер<br/><input type="text" name="host" value="localhost"/><br/>';
        echo 'Имя пользователя<br/><input type="text" name="user" /><br/>';
        echo 'Пароль<br/><input type="password" name="pass" /><br/>';
        echo 'Название базы<br/><input type="text" name="name" /><br/><br/>';
        echo '<input type="submit" value="Ok!"/><br/></div><br/>';
        echo '</form>';
        break;

    default:
        ////////////////////////////////////////////////////////////
        // Предварительная проверка системы                       //
        // 1) Проверка настроек PHP                               //
        // 2) Проверка необходимых расширений PHP                 //
        // 2) Проверка прав доступа к файлам и папкам             //
        ////////////////////////////////////////////////////////////
        $err = false;
        echo '<b>ПРОВЕРКА СИСТЕМЫ</b>';

        // Проверка настроек PHP
        echo '<hr/><b>Настройки PHP</b><br />';
        if (version_compare(phpversion(), '4.1.0', '>'))
        {
            echo '<span class="green">OK</span> - Версия PHP ' . phpversion() . '<br />';
        } else
        {
            $err = 1;
            echo '<span class="red">ОШИБКА!</span> - Версия PHP ' . phpversion() . '<br />';
            echo '<span class="gray">Эта версия PHP устаревшая и не поддерживается системой.</span><br />';
        }
        if (!ini_get('register_globals'))
        {
            echo '<span class="green">OK</span> - register_globals OFF<br />';
        } else
        {
            $err = 2;
            echo '<span class="red">ВНИМАНИЕ!</span> - register_globals OFF<br />';
            echo '<span class="gray">Вы можете продолжить установку, однако система в большей степени будет подвержена уязвимостям.</span><br />';
        }
        if (ini_get('arg_separator.output') == '&amp;')
        {
            echo '<span class="green">OK</span> - arg_separator.output "&amp;amp;"<br />';
        } else
        {
            $err = 2;
            echo '<span class="red">ВНИМАНИЕ!</span> - arg_separator.output "' . htmlentities(ini_get('arg_separator.output')) . '"<br />';
            echo '<span class="gray">Вы можете продолжить установку, однако настоятельно рекомендуется установить этот параметр на "&amp;amp;",<br /> иначе будут неправильно обрабатываться гиперссылки в XHTML.</span><br />';
        }
        if (!ini_get('magic_quotes_gpc'))
        {
            echo '<span class="green">OK</span> - magic_quotes_gpc OFF<br />';
        } else
        {
            $err = 1;
            echo '<span class="red">ОШИБКА!</span> - magic_quotes_gpc ON<br />';
            echo '<span class="gray">Необходимо установить magic_quotes_gpc OFF.</span><br />';
        }
        if (!ini_get('magic_quotes_runtime'))
        {
            echo '<span class="green">OK</span> - magic_quotes_runtime OFF<br />';
        } else
        {
            $err = 1;
            echo '<span class="red">ОШИБКА!</span> - magic_quotes_runtime ON<br />';
            echo '<span class="gray">Необходимо установить magic_quotes_runtime OFF.</span><br />';
        }

        // Проверка загрузки необходимых расширений PHP
        echo '<hr /><b>Расширения PHP</b><br />';
        if (extension_loaded('mysql'))
        {
            echo '<span class="green">OK</span> - mysql<br />';
        } else
        {
            $err = 1;
            echo '<span class="red">ОШИБКА! - PHP расширение "mysql" не загружено.</span><br />';
        }
        if (extension_loaded('gd'))
        {
            echo '<span class="green">OK</span> - gd<br />';
        } else
        {
            $err = 1;
            echo '<span class="red">ОШИБКА! - PHP расширение "gd" не загружено.</span><br />';
        }
        if (extension_loaded('zlib'))
        {
            echo '<span class="green">OK</span> - zlib<br />';
        } else
        {
            $err = 1;
            echo '<span class="red">ОШИБКА! - PHP расширение "zlib" не загружено.</span><br />';
        }
        if (extension_loaded('iconv'))
        {
            echo '<span class="green">OK</span> - iconv<br />';
        } else
        {
            $err = 1;
            echo '<span class="red">ОШИБКА! - PHP расширение "iconv" не загружено.</span><br />';
        }
        if (extension_loaded('mbstring'))
        {
            echo '<span class="green">OK</span> - mb_string<br />';
        } else
        {
            $err = 1;
            echo '<span class="red">ОШИБКА! - PHP расширение "mbstring" не загружено.</span><br />';
            echo 'Если Вы тестируете сайт локально на "Денвере", то там, в настройках по умолчанию данное расширение не подключено.<br />';
            echo 'Вам необходимо (для Денвера) открыть файл php.ini, который находится в папке /usr/local/php5 (или php4, в зависимости от версии) и отредактировать строку ;extension=php_mbstring.dll убрав точку с запятой в начале строки.';
        }

        // Проверка прав доступа к файлам и папкам
        function permissions($filez) {
            $filez = @decoct(@fileperms("$filez")) % 1000;
            return $filez;
        }
        $cherr = "";
        $arr = array("../incfiles", "../gallery/foto", "../gallery/temp", "../str/temp", "../pratt", "../forum/files", "../forum/temtemp", "../download/arctemp", "../download/files", "../download/graftemp", "../download/screen",
            "../download/mp3temp", "../download/upl");
        foreach ($arr as $v)
        {
            if (permissions($v) < 777)
            {
                $cherr = $cherr . '<span class="red">ОШИБКА!</span> - ' . $v . '<br/><span class="gray">Необходимо установить права доступа 777.</span><br />';
                $err = 1;
            } else
            {
                $cherr = $cherr . '<span class="green">OK</span> - ' . $v . '<br/>';
            }
        }
        echo '<hr /><b>Права доступа</b><br />';
        echo $cherr;
        echo '<hr />';
        switch ($err)
        {
            case '1':
                echo '<span class="red">ВНИМАНИЕ!</span> Имеются критические ошибки!<br />Вы не сможете продолжить инсталляцию, пока не устраните их.<br />';
                echo '<a href="index.php">Проверить заново</a>';
                break;

            case '2':
                echo '<span class="red">ВНИМАНИЕ!</span> Имеются ошибки в конфигурации!<br />Вы можете продолжить инсталляцию, однако нормальная работа системы не гарантируется.<br />';
                echo '<a href="index.php">Проверить заново</a><br /><a href="index.php?act=db">Продолжить установку</a>';
                break;

            default:
                echo '<span class="green">ОТЛИЧНО!</span> Все настройки правильные<br /><a href="index.php?act=db">Продолжить установку</a>';
        }
        break;
}
echo "</body></html>";

?>