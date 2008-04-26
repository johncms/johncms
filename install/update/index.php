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

define('_IN_JOHNCMS', 1);
define('_IN_PUSTO', 1);

mb_internal_encoding('UTF-8');
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
echo '<big><b>JohnCMS v.1.0.0</b></big><br />Обновление системы<hr />';
$error = false;
$do = isset($_GET['do']) ? $_GET['do'] : '';
switch ($do)
{
    case 'prepare':
        echo 'Шаг 1 из 4<hr />';
        echo '<b>Подготовка системы:</b><br />';
        // Запись файла db.php
        echo 'Файл db.php: ';
        require_once '../../incfiles/db.php';
        $text = "<?php\r\n\r\n" . "defined('_IN_JOHNCMS') or die ('Error: restricted access');\r\n\r\n" . "$" . "version_info = 100270408;\r\n\r\n" . "$" . "db_host=\"$db_host\";\r\n" . "$" . "db_user=\"$db_user\";\r\n" . "$" . "db_pass=\"$db_pass\";\r\n" . "$" . "db_name=\"$db_name\";\r\n" .
            "\r\n?>";
        $fp = @fopen("../../incfiles/db.php", "w");
        @fputs($fp, $text) or die('<span class="red">Ошибка</span></body></html>');
        fclose($fp);
        echo '<span class="green">OK</span>';
        // Подключаемся к базе данных
        echo '<br />Подключаемся к базе данных: ';
        mysql_connect($db_host, $db_user, $db_pass) or die('<span class="red">Cannot connect to server</span></body></html>');
        mysql_select_db($db_name) or die('<span class="red">Cannot select db</span></body></html>');
        echo '<span class="green">OK</span><br /><br />';

        ////////////////////////////////////////////////////////////
        // Подготовка таблиц                                      //
        ////////////////////////////////////////////////////////////
        echo '<b>Подготовка таблиц:</b>';

        // Удаление таблиц
        mysql_query("DROP TABLE IF EXISTS `upload`;");
        mysql_query("DROP TABLE IF EXISTS `themes`;");
        mysql_query("DROP TABLE IF EXISTS `bann`;");

        // Модификация таблицы "chat"
        echo '<br />Таблица "chat": ';
        mysql_query("ALTER TABLE `chat` ADD INDEX `refid` ( `refid` );");
        mysql_query("ALTER TABLE `chat` ADD INDEX `type` ( `type` );");
        mysql_query("ALTER TABLE `chat` ADD INDEX `time` ( `time` );");
        mysql_query("ALTER TABLE `chat` ADD INDEX `from` ( `from` );");
        mysql_query("ALTER TABLE `chat` ADD INDEX `to` ( `to` );");
        echo '<span class="green">OK</span>';

        // Модификация таблицы "count"
		echo '<br />Таблица "count": ';
        mysql_query("ALTER TABLE `count` ADD INDEX `time` ( `time` );");
        mysql_query("ALTER TABLE `count` ADD INDEX `where` ( `where` );");
        mysql_query("ALTER TABLE `count` ADD INDEX `name` ( `name` );");
        echo '<span class="green">OK</span>';

        // Модификация таблицы "download"
		echo '<br />Таблица "download": ';
        mysql_query("ALTER TABLE `download` ADD INDEX `type` ( `type` );");
        mysql_query("ALTER TABLE `download` ADD INDEX `refid` ( `refid` );");
        mysql_query("ALTER TABLE `download` ADD INDEX `time` ( `time` );");
        echo '<span class="green">OK</span>';

        // Модификация таблицы "gallery"
		echo '<br />Таблица "gallery": ';
        mysql_query("ALTER TABLE `gallery` ADD INDEX `refid` ( `refid` );");
        mysql_query("ALTER TABLE `gallery` ADD INDEX `type` ( `type` );");
        mysql_query("ALTER TABLE `gallery` ADD INDEX `time` ( `time` );");
        mysql_query("ALTER TABLE `gallery` ADD INDEX `avtor` ( `avtor` );");
        echo '<span class="green">OK</span>';

        // Модификация таблицы "guest"
		echo '<br />Таблица "guest": ';
        mysql_query("TRUNCATE TABLE `guest`;");
        mysql_query("ALTER TABLE `guest` CHANGE `ip` `ip` int(11) NOT NULL;");
		mysql_query("ALTER TABLE `guest` ADD `user_id` int(11) NOT NULL AFTER `time`;");
		mysql_query("ALTER TABLE `guest` ADD `edit_who` varchar(20) NOT NULL;");
        mysql_query("ALTER TABLE `guest` ADD `edit_time` int(11) NOT NULL;");
        mysql_query("ALTER TABLE `guest` ADD `edit_count` tinyint(4) NOT NULL default '0';");
        mysql_query("ALTER TABLE `guest` ADD INDEX `soft` ( `soft` );");
        mysql_query("ALTER TABLE `guest` ADD INDEX `time` ( `time` );");
        mysql_query("ALTER TABLE `guest` ADD INDEX `ip` ( `ip` );");
        echo '<span class="green">OK</span>';

        echo '<br />Таблица "moder": ';
        mysql_query("ALTER TABLE `moder` ADD `user_id` int(11) NOT NULL AFTER `to`;");
        echo '<span class="green">OK</span>';

        echo '<br />Таблица "privat": ';
        mysql_query("ALTER TABLE `privat` ADD INDEX `me` ( `me` );");
        mysql_query("ALTER TABLE `privat` ADD INDEX `ignor` ( `ignor` );");
        echo '<span class="green">OK</span>';

        echo '<br />Таблица "settings": ';
        mysql_query("ALTER TABLE `settings` ADD `clean_time` int(11) NOT NULL;");
        echo '<span class="green">OK</span>';

        echo '<hr /><a href="index.php?do=users">Продолжить</a>';
        break;

    case 'users':
        echo 'Шаг 2 из 4<hr />';
        echo '<b>Перенос пользователей:</b><br />';
        require_once 'users.php';
        break;

    case 'library':
        echo 'Шаг 3 из 4<hr />';
        echo '<b>Конвертация Библиотеки:</b><br />';
        require_once 'library.php';
        break;

    case 'forum':
        echo 'Шаг 4 из 4<hr />';
        echo '<b>Конвертация Форума:</b><br />';
        require_once 'forum.php';
        break;

    case 'final':
        echo '<b><span class="green">Поздравляем!</span></b><br />Процедура обновления успешно завершена.<br />Не забудьте удалить папку /install';
        echo '<hr /><a href="../../index.php">На сайт</a>';
        break;

    default:
        function permissions($filez) {
            $filez = @decoct(@fileperms("$filez")) % 1000;
            return $filez;
        }
        echo '<p><big><span class="red">ВНИМАНИЕ!</span></big><br />Перед началом процедуры обновления, ОБЯЗАТЕЛЬНО сделайте резервную копию базы данных.
		<br />Если по какой то причине обновление не пройдет до конца, Вам придется восстанавливать базу из резервной копии.</p>';
        if (!file_exists('../../incfiles/db.php'))
        {
            $error = 1;
            echo '<span class="red">Ошибка</span><br />Отсутствует файл /incdiles/db.php<br />Обновление невозможно.<br />';
        } elseif (permissions('../../incfiles/db.php') < 666)
        {
            $error = 1;
            echo '<span class="red">Ошибка</span><br />Необходимо установить права доступа 666 на файл <b>/incdiles/db.php</b><br />Обновление невозможно.<br />';
        } else
        {
            require_once '../../incfiles/db.php';
            if (!isset($db_host) || !isset($db_user) || !isset($db_name))
            {
                $error = 1;
                echo '<span class="red">Ошибка</span><br />Файл <b>/incdiles/db.php</b> поврежден.<br />Обновление невозможно.<br />';
            }
        }
        echo '<hr /><a href="../">Назад</a>';
        if (!$error)
            echo '<br />Внимание!<br />Если Вы нажмете ссылку "Продолжить", то отмена изменений будет невозможна без восстановления из резервной копии.<br />Вы уверены?';
        echo '<br /><a href="index.php?do=prepare">Продолжить</a>';
}

echo '</body>
</html>';

?>