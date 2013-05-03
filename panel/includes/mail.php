<?php

/**
* @package     JohnCMS
* @link        http://johncms.com
* @copyright   Copyright (C) 2008-2011 JohnCMS Community
* @license     LICENSE.txt (see attached file)
* @version     VERSION.txt (see attached file)
* @author      http://johncms.com/about
*/

defined('_IN_JOHNADM') or die('Error: restricted access');

// Проверяем права доступа
if ($rights < 9) {
    header('Location: http://johncms.com/?err');
    exit;
}
$lng_mail = core::load_lng('mail');

echo '<div class="phdr"><a href="index.php"><b>' . $lng['admin_panel'] . '</b></a> | ' . $lng['mail'] . '</div>';
if (isset($_POST['submit'])) {
    /*
    -----------------------------------------------------------------
    Сохраняем настройки системы
    -----------------------------------------------------------------
    */
	$set_mail['cat_friends'] = isset($_POST['cat_friends']) && $_POST['cat_friends'] == 1 ? 1 : 0;
	$set_mail['message_include'] = isset($_POST['message_include']) && $_POST['message_include'] == 1 ? 1 : 0;
	mysql_query("UPDATE `cms_settings` SET `val`='" . mysql_real_escape_string($_POST['them_message']) . "' WHERE `key` = 'them_message'");
    mysql_query("UPDATE `cms_settings` SET `val`='" . mysql_real_escape_string($_POST['reg_message']) . "' WHERE `key` = 'reg_message'");
    mysql_query("UPDATE `cms_settings` SET `val`='" . (isset($_POST['message_include']) && $_POST['message_include'] == 1 ? 1 : 0) . "' WHERE `key` = 'setting_mail'");
	mysql_query("UPDATE `cms_settings` SET `val`='" . mysql_real_escape_string(serialize($set_mail)) . "' WHERE `key` = 'setting_mail'");
	$req = mysql_query("SELECT * FROM `cms_settings`");
    $set = array ();
    while ($res = mysql_fetch_row($req)) $set[$res[0]] = $res[1];
    echo '<div class="rmenu">' . $lng['settings_saved'] . '</div>';
}
$set_mail = unserialize($set['setting_mail']);
if(!isset($set_mail['cat_friends']))
	$set_mail['cat_friends'] = 0;
if(!isset($set_mail['message_include']))
	$set_mail['message_include'] = 0;
/*
-----------------------------------------------------------------
Форма ввода параметров системы
-----------------------------------------------------------------
*/
if(empty($set['them_message']))
	$set['them_message'] = $lng_mail['them_message'];
if(empty($set['reg_message']))
	$set['reg_message'] = $lng['hi'] . ", {LOGIN}\r\n" . $lng_mail['pleased_see_you'] . "\r\n" . $lng_mail['come_my_site'] . "\r\n" . $lng_mail['respectfully_yours'];

echo '<form action="index.php?act=mail" method="post"><div class="menu">';
// Общие настройки
echo '<h3>' . $lng_mail['system_message_reg'] . '</h3>' . $lng_mail['theme_system_message'] . ':<br/>' . 
	'<input type="text" name="them_message" value="' . (!empty($set['them_message']) ? htmlentities($set['them_message'], ENT_QUOTES, 'UTF-8') : '') . '"/><br/>' .
    $lng['message'] . ':<br /><textarea rows="' . $set_user['field_h'] . '" name="reg_message">' . (!empty($set['reg_message']) ? htmlentities($set['reg_message'], ENT_QUOTES, 'UTF-8') : '') . '</textarea><br />' .
	'<strong>' . $lng_mail['sending_the_message'] . ':</strong><br />' .
	'<input type="radio" value="1" name="message_include" ' . ($set_mail['message_include'] == 1 ? 'checked="checked"' : '') . '/>&#160;' . $lng['lng_on'] . '<br />' .
    '<input type="radio" value="0" name="message_include" ' . (empty($set_mail['message_include']) ? 'checked="checked"' : '') . '/>&#160;' . $lng['lng_off'] . '<br />' .
	'<strong>' . $lng_mail['marks'] . ':</strong><br />{LOGIN} - ' . $lng_mail['login_contacts'] . '<br />{TIME} - ' . $lng_mail['current_time'] . '<br />' .
	'<strong>' . $lng_mail['cat_friends'] . ':</strong><br />' .
	'<input type="radio" value="1" name="cat_friends" ' . ($set_mail['cat_friends'] == 1 ? 'checked="checked"' : '') . '/>&#160;' . $lng['lng_on'] . '<br />' .
    '<input type="radio" value="0" name="cat_friends" ' . (empty($set_mail['cat_friends']) ? 'checked="checked"' : '') . '/>&#160;' . $lng['lng_off'] . '<br />' .
	'<p><input type="submit" name="submit" value="' . $lng['save'] . '"/></p></div></form>' .
    '<div class="phdr"><a href="index.php">' . $lng['admin_panel'] . '</a></div>';
?>