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
        mysql_query("ALTER TABLE `cms_ban_users` ADD `ban_ref` INT NOT NULL AFTER `ban_who` ;") or die('Error</body></html>');
        echo '<span class="green">OK</span> таблица банов обновлена.<br />';
        echo '<hr /><a href="up_150_160.php?do=final">Продолжить</a>';
        break;

    case 'final':
        echo '<b><span class="green">Поздравляем!</span></b><br />Процедура обновления успешно завершена.<br />Не забудьте удалить папку /install';
        echo '<hr /><a href="../../index.php">На сайт</a>';
        break;

    default:
        echo '<p><big><span class="red">ВНИМАНИЕ!</span></big><br />Перед началом процедуры обновления, ОБЯЗАТЕЛЬНО сделайте резервную копию базы данных.';
        echo '<br />Если по какой то причине обновление не пройдет до конца, Вам придется восстанавливать базу из резервной копии.</p>';
        echo '<p>Учтите, что обновление возможно только для версий системы 1.5.0</p>';
        echo '<hr />Если Вы нажмете ссылку "Продолжить", то отмена изменений будет невозможна без восстановления из резервной копии.<br />Вы уверены?';
        echo '<br /><a href="up_150_160.php?do=update">Продолжить</a>';
}

echo '</body>
</html>';

?>