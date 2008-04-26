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

function unhtmlentities($string)
{
    $string = preg_replace('~&#x([0-9a-f]+);~ei', 'chr(hexdec("\\1"))', $string);
    $string = preg_replace('~&#([0-9]+);~e', 'chr("\\1")', $string);
    $trans_tbl = get_html_translation_table(HTML_ENTITIES);
    $trans_tbl = array_flip($trans_tbl);
    return strtr($string, $trans_tbl);
}

$connect = mysql_connect($db_host, $db_user, $db_pass) or die('<span class="red">Cannot connect to server</span></body></html>');
mysql_select_db($db_name) or die('<span class="red">Cannot select db</span></body></html>');
mysql_query("SET NAMES 'utf8'", $connect);

// Модификация таблицы
@mysql_query("ALTER TABLE `lib` CHANGE `ip` `ip` int(11) NOT NULL;");
@mysql_query("ALTER TABLE `lib` ADD `announce` TEXT NOT NULL AFTER `name` ;");
@mysql_query("ALTER TABLE `lib` ADD `count` INT DEFAULT '0' NOT NULL ;");
@mysql_query("ALTER TABLE `lib` ADD INDEX `type` ( `type` ) ;");
@mysql_query("ALTER TABLE `lib` ADD INDEX `moder` ( `moder` ) ;");
@mysql_query("ALTER TABLE `lib` ADD INDEX `time` ( `time` ) ;");
@mysql_query("ALTER TABLE `lib` ADD INDEX `refid` ( `refid` ) ;");
$req = mysql_query("SELECT * FROM `lib` WHERE `type`='bk';");
while ($res = mysql_fetch_array($req))
{
    $name = $res['name'];
    $announce = $res['soft'];
    $text = $res['text'];

    $name = str_replace("<br/>", "\r\n", $name);
    $announce = str_replace("<br/>", "\r\n", $announce);
    $text = str_replace("<br/>", "\r\n", $text);

    if (version_compare(phpversion(), '5.0.0', '>'))
    {
        $name = html_entity_decode($name);
        $announce = html_entity_decode($announce);
        $text = html_entity_decode($text);
    } else
    {
        $name = unhtmlentities($name);
        $announce = unhtmlentities($announce);
        $text = unhtmlentities($text);
    }

    if (empty($announce))
    {
        $announce = mb_substr($text, 0, 100);
    }

    mysql_query("update `lib` set
	`name`='" . mysql_real_escape_string($name) . "',
	`announce`='" . mysql_real_escape_string($announce) . "',
	`text`='" . mysql_real_escape_string($text) . "',
	`soft`=''
	where id='" . $res['id'] . "';");
    $i++;
}
echo '<span class="green">Библиотека готова.</span><br />Обработано ' . $i . ' записей';
echo '<hr /><a href="index.php?do=forum">Продолжить</a>';

?>