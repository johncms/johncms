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

function rus_lat($str)
{
    $str = strtr($str, array('а' => 'a', 'б' => 'b', 'в' => 'v', 'г' => 'g', 'д' => 'd', 'е' => 'e', 'ё' => 'e', 'ж' => 'j', 'з' => 'z', 'и' => 'i', 'й' => 'i', 'к' => 'k', 'л' => 'l', 'м' => 'm', 'н' => 'n', 'о' => 'o', 'п' => 'p', 'р' => 'r',
        'с' => 's', 'т' => 't', 'у' => 'u', 'ф' => 'f', 'х' => 'h', 'ц' => 'c', 'ч' => 'ch', 'ш' => 'sh', 'щ' => 'sch', 'ъ' => "", 'ы' => 'y', 'ь' => "", 'э' => 'ye', 'ю' => 'yu', 'я' => 'ya'));
    return $str;
}

$connect = mysql_connect($db_host, $db_user, $db_pass) or die('<span class="red">Cannot connect to server</span></body></html>');
mysql_select_db($db_name) or die('<span class="red">Cannot select db</span></body></html>');
mysql_query("SET NAMES 'utf8'", $connect);

// Модификация таблицы "users"
echo 'Таблица "users": ';
@mysql_query("ALTER TABLE `users` DROP `vremja`;");
@mysql_query("ALTER TABLE `users` DROP `bgcolor`;");
@mysql_query("ALTER TABLE `users` DROP `bclass`;");
@mysql_query("ALTER TABLE `users` DROP `cclass`;");
@mysql_query("ALTER TABLE `users` DROP `dclass`;");
@mysql_query("ALTER TABLE `users` DROP `tex`;");
@mysql_query("ALTER TABLE `users` DROP `link`;");
@mysql_query("ALTER TABLE `users` DROP `cntem`;");
@mysql_query("ALTER TABLE `users` DROP `ccolp`;");
@mysql_query("ALTER TABLE `users` DROP `cdtim`;");
@mysql_query("ALTER TABLE `users` DROP `cssip`;");
@mysql_query("ALTER TABLE `users` DROP `csnik`;");
@mysql_query("ALTER TABLE `users` DROP `conik`;");
@mysql_query("ALTER TABLE `users` DROP `cadms`;");
@mysql_query("ALTER TABLE `users` DROP `cons`;");
@mysql_query("ALTER TABLE `users` DROP `coffs`;");
@mysql_query("ALTER TABLE `users` DROP `cdinf`;");
@mysql_query("ALTER TABLE `users` DROP `cpfon`;");
@mysql_query("ALTER TABLE `users` DROP `ccfon`;");
@mysql_query("ALTER TABLE `users` ADD `name_lat` VARCHAR( 40 ) NOT NULL AFTER `name`;");
@mysql_query("ALTER TABLE `users` ADD `total_on_site` int(11) NOT NULL default '0';");
@mysql_query("ALTER TABLE `users` ADD INDEX `name_lat` ( `name_lat` );");
@mysql_query("ALTER TABLE `users` ADD INDEX `lastdate` ( `lastdate` );");
@mysql_query("ALTER TABLE `users` CHANGE `timererfesh` `timererfesh` int(2) NOT NULL default '20';");
@mysql_query("ALTER TABLE `users` CHANGE `kolanywhwere` `kolanywhwere` int(2) NOT NULL default '10';");
@mysql_query("ALTER TABLE `users` CHANGE `offpg` `offpg` BOOL NOT NULL DEFAULT '0';");
@mysql_query("ALTER TABLE `users` CHANGE `offgr` `offgr` BOOL NOT NULL DEFAULT '0';");
@mysql_query("ALTER TABLE `users` CHANGE `offsm` `offsm` BOOL NOT NULL DEFAULT '0';");
@mysql_query("ALTER TABLE `users` CHANGE `offtr` `offtr` BOOL NOT NULL DEFAULT '0';");
@mysql_query("ALTER TABLE `users` CHANGE `pereh` `pereh` BOOL NOT NULL DEFAULT '0';");
@mysql_query("ALTER TABLE `users` CHANGE `upfp` `upfp` BOOL NOT NULL DEFAULT '0';");
@mysql_query("ALTER TABLE `users` CHANGE `farea` `farea` BOOL NOT NULL DEFAULT '0';");
@mysql_query("ALTER TABLE `users` CHANGE `chmes` `chmes` int(2) NOT NULL default '10';");
@mysql_query("ALTER TABLE `users` CHANGE `carea` `carea` BOOL NOT NULL DEFAULT '0';");
echo '<span class="green">OK</span><br />';

$req = mysql_query("SELECT * FROM `users`;");
while ($res = mysql_fetch_array($req))
{
    $user_lat = rus_lat(mb_strtolower($res['name']));
    mysql_query("update `users` set `name_lat`='" . mysql_real_escape_string($user_lat) . "' where id='" . $res['id'] . "';");
	$i++;
}
echo '<span class="green">Пользователи готовы.</span><br />Обработано ' . $i . ' записей';
echo '<hr /><a href="index.php?do=library">Продолжить</a>';

?>