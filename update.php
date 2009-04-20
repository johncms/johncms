<?php

define('_IN_JOHNCMS', 1);

header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");
header("Cache-Control: no-cache, must-revalidate");
header("Pragma: no-cache");
header("Last-Modified: " . gmdate("D, d M Y H:i:s") . "GMT");
header("Content-type: application/xhtml+xml; charset=UTF-8");
echo "<!DOCTYPE html PUBLIC '-//W3C//DTD XHTML 1.0 Strict//EN' 'http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd'>
<html xmlns='http://www.w3.org/1999/xhtml' xml:lang='en'>
<head>
<meta http-equiv='content-type' content='application/xhtml+xml; charset=utf-8'/>";
echo "<title>Обновление системы</title>
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
echo '<big><b>JohnCMS v.2.4.0</b></big><br />Обновление с версии 2.3.0<hr />';

// Подключаемся к базе данных
require_once ("incfiles/db.php");
require_once ("incfiles/func.php");
$connect = mysql_connect($db_host, $db_user, $db_pass) or die('cannot connect to server</body></html>');
mysql_select_db($db_name) or die('cannot connect to db');
mysql_query("SET NAMES 'utf8'", $connect);

$do = isset($_GET['do']) ? $_GET['do'] : '';
switch ($do)
{
    case 'step1':
        echo '<b><u>Права доступа</u></b><br />';
        // Проверка прав доступа к файлам и папкам
        function permissions($filez) {
            $filez = @decoct(@fileperms($filez)) % 1000;
            return $filez;
        }
        $cherr = '';
        $err = false;
        // Проверка прав доступа к папкам
        $arr = array("gallery/foto/", "gallery/temp/", "library/files/", "library/temp/", "pratt/", "forum/files/", "forum/temtemp/", "download/arctemp/", "download/files/", "download/graftemp/", "download/screen/", "download/mp3temp/",
            "download/upl/");
        foreach ($arr as $v)
        {
            if (permissions($v) < 777)
            {
                $cherr = $cherr . '<div class="smenu"><span class="red">Ошибка!</span> - ' . $v . '<br /><span class="gray">Необходимо установить права доступа 777.</span></div>';
                $err = 1;
            } else
            {
                $cherr = $cherr . '<div class="smenu"><span class="green">Oк</span> - ' . $v . '</div>';
            }
        }
        // Проверка прав доступа к файлам
        $arr = array('flood.dat', 'library/java/textfile.txt', 'library/java/META-INF/MANIFEST.MF', 'panel/filebase.dat');
        foreach ($arr as $v)
        {
            if (permissions($v) < 666)
            {
                $cherr = $cherr . '<div class="smenu"><span class="red">Ошибка!</span> - ' . $v . '<br/><span class="gray">Необходимо установить права доступа 666.</span></div>';
                $err = 1;
            } else
            {
                $cherr = $cherr . '<div class="smenu"><span class="green">Ок</span> - ' . $v . '</div>';
            }
        }
        echo '<div class="menu">';
        echo $cherr;
        echo '</div><br />';
        if ($err)
        {
            echo '<span class="red">Внимание!</span> Имеются критические ошибки!<br />Вы не сможете продолжить инсталляцию, пока не устраните их.';
            echo '<p clss="step"><a class="button" href="index.php?act=check">Проверить заново</a></p>';
        } else
        {
            echo '<span class="green">Отлично!</span><br />Все настройки правильные.<hr /><a class="button" href="update.php?do=step2">Продолжить</a>';
        }
        break;

    case 'step2':
        echo '<b><u>Подготовка таблиц</u></b><br />';
        // Таблица счетчиков
		mysql_query("DROP TABLE IF EXISTS `cms_counters`");
		mysql_query("CREATE TABLE IF NOT EXISTS `cms_counters` (
		`id` int(11) NOT NULL auto_increment,
		`sort` int(11) NOT NULL default '1',
		`name` varchar(30) NOT NULL,
		`link1` text NOT NULL,
		`link2` text NOT NULL,
		`mode` tinyint(4) NOT NULL default '1',
		`switch` tinyint(1) NOT NULL default '0',
		PRIMARY KEY  (`id`)
		) ENGINE=MyISAM  DEFAULT CHARSET=utf8;");
        echo '<span class="green">OK</span> таблица cms_counters создана<br />';
        // Таблица бана по IP
		mysql_query("DROP TABLE IF EXISTS `cms_ban_ip`");
		mysql_query("CREATE TABLE `cms_ban_ip` (
		`id` int(11) NOT NULL auto_increment,
		`ip1` int(11) NOT NULL,
		`ip2` int(11) NOT NULL,
		`ban_type` tinyint(4) NOT NULL default '0',
		`link` varchar(100) NOT NULL,
		`who` varchar(25) NOT NULL,
		`reason` text NOT NULL,
		`date` int(11) NOT NULL,
		PRIMARY KEY  (`id`),
		UNIQUE KEY `ip1` (`ip1`),
		UNIQUE KEY `ip2` (`ip2`)
		) ENGINE=MyISAM  DEFAULT CHARSET=utf8;");
        echo '<span class="green">OK</span> таблица cms_ban_ip создана<br />';
        echo '<hr /><a href="update.php?do=final">Продолжить</a>';
        break;

    case 'final':
        echo '<b><span class="green">Поздравляем!</span></b><br />Процедура обновления успешно завершена.<br />Не забудьте удалить папку /install';
        echo '<hr /><a href="../../index.php">На сайт</a>';
        break;

    default:
        echo '<p><big><span class="red">ВНИМАНИЕ!</span></big><ul>';
        echo '<li>Учтите, что обновление возможно только для системы <b>JohnCMS 2.3.0</b></li>';
        echo '<li>Если Вы используете какие-либо моды, то возможность обновления обязательно согласуйте с их авторами.</li>';
        echo '<li>Перед началом процедуры обновления, ОБЯЗАТЕЛЬНО сделайте резервную копию базы данных. Если по какой то причине обновление не пройдет до конца, Вам придется восстанавливать базу из резервной копии.</li>';
        echo '<li>Если Вы нажмете ссылку "Продолжить", то отмена изменений будет невозможна без восстановления из резервной копии.</li>';
        echo '<li></li>';
        echo '</ul></p>';
        echo '<hr />Вы уверены?<br /><a href="update.php?do=step1">Продолжить</a>';
}

echo '</body>
</html>';

?>