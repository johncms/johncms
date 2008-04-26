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

defined('_IN_JOHNCMS') or die('Error:restricted access');

require_once '../../incfiles/db.php';

$connect = mysql_connect($db_host, $db_user, $db_pass) or die('<span class="red">Cannot connect to server</span></body></html>');
mysql_select_db($db_name) or die('<span class="red">Cannot select db</span></body></html>');
mysql_query("SET NAMES 'utf8'", $connect);

// Модификация таблицы "forum"
echo 'Таблица "forum": ';
mysql_query("ALTER TABLE `forum` ADD `dlcount` int(11) NOT NULL default '0';");
mysql_query("ALTER TABLE `forum` ADD INDEX `refid` ( `refid` );");
mysql_query("ALTER TABLE `forum` ADD INDEX `type` ( `type` );");
mysql_query("ALTER TABLE `forum` ADD INDEX `time` ( `time` );");
mysql_query("ALTER TABLE `forum` ADD INDEX `from` ( `from` );");
mysql_query("ALTER TABLE `forum` ADD INDEX `to` ( `to` );");
mysql_query("ALTER TABLE `forum` ADD INDEX `moder` ( `moder` );");
echo '<span class="green">OK</span><br />';

$req = mysql_query("SELECT * FROM `forum`");
while ($res = mysql_fetch_array($req))
{
    $i++;
}
echo '<span class="green">Форум готов.</span><br />Обработано ' . $i . ' записей';
echo '<hr /><a href="index.php?do=final">Продолжить</a>';

?>