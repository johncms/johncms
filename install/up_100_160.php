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
echo "<link rel='shortcut icon' href='ico.gif' />
<title>Обновление системы</title>
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
echo '<big><b>JohnCMS v.1.6.0</b></big><br />Обновление системы<hr />';
$error = false;
$do = isset($_GET['do']) ? $_GET['do'] : '';
switch ($do)
{
    case 'update':
        require_once ("../incfiles/db.php");
        require_once ("../incfiles/func.php");
        $connect = mysql_connect($db_host, $db_user, $db_pass) or die('cannot connect to server</body></html>');
        mysql_select_db($db_name) or die('cannot connect to db');
        mysql_query("SET NAMES 'utf8'", $connect);
        $error = '';
        @set_magic_quotes_runtime(0);
        // Читаем SQL файл и заносим его в базу данных
        $query = fread(fopen('data/update.sql', 'r'), filesize('data/update.sql'));
        $pieces = split_sql($query);
        for ($i = 0; $i < count($pieces); $i++)
        {
            $pieces[$i] = trim($pieces[$i]);
            if (!empty($pieces[$i]) && $pieces[$i] != "#")
            {
                if (!mysql_query($pieces[$i]))
                {
                    $error = $error . mysql_error() . '<br />';
                }
            }
        }
        echo '<span class="green">OK</span> создание таблиц<br />';
        echo '<span class="green">OK</span> модификация таблиц<br />';
        // Перенос настроек системы
        $req = mysql_query("SELECT * FROM `settings`;") or die('Ошибка импорта настроек</body></html>');
        $tmp = mysql_fetch_array($req);
        mysql_query("UPDATE `cms_settings` SET `val`='" . mysql_real_escape_string($tmp['nickadmina']) . "' WHERE `key`='nickadmina';");
        mysql_query("UPDATE `cms_settings` SET `val`='" . mysql_real_escape_string($tmp['emailadmina']) . "' WHERE `key`='emailadmina';");
        mysql_query("UPDATE `cms_settings` SET `val`='" . mysql_real_escape_string($tmp['nickadmina2']) . "' WHERE `key`='nickadmina2';");
        mysql_query("UPDATE `cms_settings` SET `val`='" . intval($tmp['sdvigclock']) . "' WHERE `key`='sdvigclock';");
        mysql_query("UPDATE `cms_settings` SET `val`='" . mysql_real_escape_string($tmp['copyright']) . "' WHERE `key`='copyright';");
        mysql_query("UPDATE `cms_settings` SET `val`='" . mysql_real_escape_string($tmp['homeurl']) . "' WHERE `key`='homeurl';");
        mysql_query("UPDATE `cms_settings` SET `val`='" . mysql_real_escape_string($tmp['rashstr']) . "' WHERE `key`='rashstr';");
        mysql_query("UPDATE `cms_settings` SET `val`='" . mysql_real_escape_string($tmp['admp']) . "' WHERE `key`='admp';");
        mysql_query("UPDATE `cms_settings` SET `val`='" . intval($tmp['flsz']) . "' WHERE `key`='flsz';");
        mysql_query("UPDATE `cms_settings` SET `val`='" . intval($tmp['gzip']) . "' WHERE `key`='gzip';");
        mysql_query("UPDATE `cms_settings` SET `val`='" . intval($tmp['gb']) . "' WHERE `key`='gb';");
        // Удаление старой таблицы настроек
        mysql_query("DROP TABLE IF EXISTS `settings`;");
        echo '<span class="green">OK</span> перенос настроек<br />';
        echo '<hr /><a href="up_100_160.php?do=final">Продолжить</a>';
        break;

    case 'final':
        echo '<b><span class="green">Поздравляем!</span></b><br />Процедура обновления успешно завершена.<br />Не забудьте удалить папку /install';
        echo '<hr /><a href="../../index.php">На сайт</a>';
        break;

    default:
        echo '<p><big><span class="red">ВНИМАНИЕ!</span></big><br />Перед началом процедуры обновления, ОБЯЗАТЕЛЬНО сделайте резервную копию базы данных.';
        echo '<br />Если по какой то причине обновление не пройдет до конца, Вам придется восстанавливать базу из резервной копии.</p>';
        echo '<p>Учтите, что обновление возможно только для версий системы 1.0.0, или 1.0.1<br />Бета и RC-1, RC-2 версии с данным обновлением несовместимы.</p>';
        echo '<hr />Если Вы нажмете ссылку "Продолжить", то отмена изменений будет невозможна без восстановления из резервной копии.<br />Вы уверены?';
        echo '<br /><a href="up_100_160.php?do=update">Продолжить</a>';
}

echo '</body>
</html>';

function split_sql($sql)
{
    $sql = trim($sql);
    $sql = ereg_replace("\n#[^\n]*\n", "\n", $sql);
    $buffer = array();
    $ret = array();
    $in_string = false;
    for ($i = 0; $i < strlen($sql) - 1; $i++)
    {
        if ($sql[$i] == ";" && !$in_string)
        {
            $ret[] = substr($sql, 0, $i);
            $sql = substr($sql, $i + 1);
            $i = 0;
        }
        if ($in_string && ($sql[$i] == $in_string) && $buffer[1] != "\\")
        {
            $in_string = false;
        } elseif (!$in_string && ($sql[$i] == '"' || $sql[$i] == "'") && (!isset($buffer[0]) || $buffer[0] != "\\"))
        {
            $in_string = $sql[$i];
        }
        if (isset($buffer[1]))
        {
            $buffer[0] = $buffer[1];
        }
        $buffer[1] = $sql[$i];
    }
    if (!empty($sql))
    {
        $ret[] = $sql;
    }
    return ($ret);
}

?>