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
echo '<big><b>JohnCMS v.2.0.0</b></big> Обновление<hr />';

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
        echo '<b><u>Подготовка таблиц</u></b><br />';
        // Создаем таблицу меток прочтения
        mysql_query("DROP TABLE IF EXISTS `cms_forum_rdm`");
        mysql_query("CREATE TABLE `cms_forum_rdm` (
        `topic_id` int(11) NOT NULL,
        `user_id` int(11) NOT NULL,
        `time` int(11) NOT NULL,
        PRIMARY KEY  (`topic_id`,`user_id`),
        KEY `time` (`time`)
        ) ENGINE=MyISAM DEFAULT CHARSET=utf8");
        echo '<span class="green">OK</span> таблица меток готова<br />';
        // Модифицируем таблицу `users`
        mysql_query("ALTER TABLE `users` ADD `digest` TINYINT NOT NULL DEFAULT '1'");
        mysql_query("ALTER TABLE `users` ADD `skin` varchar(20) NOT NULL");
        mysql_query("ALTER TABLE `users` CHANGE `offsm` `offsm` TINYINT( 1 ) NOT NULL DEFAULT '1'");
        mysql_query("ALTER TABLE `users` CHANGE `offtr` `offtr` TINYINT( 1 ) NOT NULL DEFAULT '1'");
        mysql_query("ALTER TABLE `users` CHANGE `pereh` `pereh` TINYINT( 1 ) NOT NULL DEFAULT '1'");
        echo '<span class="green">OK</span> таблица users готова<br />';
        // Конвертируем пользовательские настройки
        $req = mysql_query("SELECT `id` FROM `users`");
        while ($res = mysql_fetch_array($req))
        {
            mysql_query("UPDATE `users` SET `offsm`='1', `offtr`='1', `pereh`='1' WHERE `id`='" . $res['id'] . "';");
        }
        mysql_query("INSERT INTO `cms_settings` SET `key`='skindef', `val`='default';");
		echo '<span class="green">OK</span> настройки готовы<br />';
        echo '<hr /><a href="update.php?do=step2">Продолжить</a>';
        break;

    case 'step2':
        echo '<b><u>Удаление меток форума</u></b><br />';
        echo '<p>Внимание!<br />На больших форумах, данная процедура может длиться довольно долго.</p>';
        // Удаляем метки (l)
        $req = mysql_query("DELETE FROM `forum` WHERE `type`='l';");
        echo '<span class="green">OK</span> метки удалены.<br />';
        echo '<hr /><a href="update.php?do=step3">Продолжить</a>';
        break;

    case 'step3':
        echo '<b><u>Конвертация форума</u></b><br />';
        echo '<p>Внимание!<br />На больших форумах, данная процедура может длиться довольно долго.</p>';
        $req = mysql_query("SELECT * FROM `forum` WHERE `type`='m';");
        while ($res = mysql_fetch_array($req))
        {
            $text = $res['text'];
            $text = str_replace("<br/>", "\r\n", $text);

            $text = str_replace('&amp;', '&', $text);
            $text = preg_replace('~&#x0*([0-9a-f]+);~ei', 'chr(hexdec("\\1"))', $text);
            $text = preg_replace('~&#0*([0-9]+);~e', 'chr(\\1)', $text);
            $trans_tbl = get_html_translation_table(HTML_ENTITIES);
            $trans_tbl = array_flip($trans_tbl);
            $text = strtr($text, $trans_tbl);

            $text = mysql_real_escape_string($text);
            mysql_query("UPDATE `forum` SET
        `text`='" . $text . "'
        WHERE `id`='" . $res['id'] . "';");
        }
        echo '<span class="green">OK</span> конвертация завершена.<br />';
		echo '<hr /><a href="update.php?do=final">Продолжить</a>';
        break;

    case 'final':
        mysql_query("OPTIMIZE TABLE `forum`;");
        echo '<b><span class="green">Поздравляем!</span></b><br />Процедура обновления успешно завершена.<br />Не забудьте удалить папку /install';
        echo '<hr /><a href="../../index.php">На сайт</a>';
        break;

    default:
        echo '<p><big><span class="red">ВНИМАНИЕ!</span></big><ul>';
        echo '<li>Учтите, что обновление возможно только для системы JohnCMS 1.6.0</li>';
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