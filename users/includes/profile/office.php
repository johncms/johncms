<?php

/**
 * @package     JohnCMS
 * @link        http://johncms.com
 * @copyright   Copyright (C) 2008-2011 JohnCMS Community
 * @license     LICENSE.txt (see attached file)
 * @version     VERSION.txt (see attached file)
 * @author      http://johncms.com/about
 */

defined('_IN_JOHNCMS') or die('Error: restricted access');
$textl = $lng_profile['my_office'];
require('../incfiles/head.php');

/*
-----------------------------------------------------------------
Проверяем права доступа
-----------------------------------------------------------------
*/
if ($user['id'] != $user_id) {
    echo functions::display_error($lng['access_forbidden']);
    require('../incfiles/end.php');
    exit;
}

/*
-----------------------------------------------------------------
Личный кабинет пользователя
-----------------------------------------------------------------
*/

$total_photo = mysql_result(mysql_query("SELECT COUNT(*) FROM `cms_album_files` WHERE `user_id` = '$user_id'"), 0);
$total_friends = mysql_result(mysql_query("SELECT COUNT(*) FROM `cms_contact` WHERE `user_id`='$user_id' AND `type`='2' AND `friends`='1'"), 0);
$new_friends = mysql_result(mysql_query("SELECT COUNT(*) FROM `cms_contact` WHERE `from_id`='$user_id' AND `type`='2' AND `friends`='0';"), 0);
$online_friends = mysql_result(mysql_query("SELECT COUNT(*) FROM `cms_contact` LEFT JOIN `users` ON `cms_contact`.`from_id`=`users`.`id` WHERE `cms_contact`.`user_id`='$user_id' AND `cms_contact`.`type`='2' AND `cms_contact`.`friends`='1' AND `lastdate` > " . (time() - 300) . ""), 0);
echo '<div class="phdr"><b>' . $lng_profile['my_office'] . '</b></div>' .
    '<div class="list2"><p>' .
    '<div><img src="../images/contacts.png" width="16" height="16"/>&#160;<a href="profile.php">' . $lng_profile['my_profile'] . '</a></div>' .
    '<div><img src="../images/rate.gif" width="16" height="16"/>&#160;<a href="profile.php?act=stat">' . $lng['statistics'] . '</a></div>' .
    '</p><p>' .
    '<div><img src="../images/photo.gif" width="16" height="16"/>&#160;<a href="album.php?act=list">' . $lng['photo_album'] . '</a>&#160;(' . $total_photo . ')</div>' .
    '<div><img src="../images/guestbook.gif" width="16" height="16"/>&#160;<a href="profile.php?act=guestbook">' . $lng['guestbook'] . '</a>&#160;(' . $user['comm_count'] . ')</div>' .
    '<div><img src="../images/users.png" width="16" height="16"/>&#160;<a href="profile.php?act=friends">' . $lng_profile['friends'] . '</a>&#160;(' . $total_friends . ($new_friends ? '/<span class="red">+' . $new_friends . '</span>' : '') . ')&#160;<a href="profile.php?act=friends&amp;do=online">' . $lng['online'] . '</a> (' . $online_friends . ')</div>';
if ($rights >= 1) {
    $guest = counters::guestbook(2);
    echo '</p><p>' .
        '<div><img src="../images/admin.png" width="16" height="16"/>&#160;<a href="../guestbook/index.php?act=ga&amp;do=set">' . $lng['admin_club'] . '</a> (<span class="red">' . $guest . '</span>)</div>';
}
echo '</p></div>';
/*
-----------------------------------------------------------------
Блок почты
-----------------------------------------------------------------
*/
echo '<div class="menu"><p><h3><img src="../images/mail.png" width="16" height="16" class="left" />&nbsp;' . $lng_profile['my_mail'] . '</h3><ul>';
//Входящие сообщения
$count_input = mysql_result(mysql_query("
	SELECT COUNT(*) 
	FROM `cms_mail` 
	LEFT JOIN `cms_contact` 
	ON `cms_mail`.`user_id`=`cms_contact`.`from_id` 
	AND `cms_contact`.`user_id`='$user_id' 
	WHERE `cms_mail`.`from_id`='$user_id' 
	AND `cms_mail`.`sys`='0' AND `cms_mail`.`delete`!='$user_id' 
	AND `cms_contact`.`ban`!='1' AND `spam`='0'"), 0);
echo '<li><a href="../mail/index.php?act=input">' . $lng_profile['received'] . '</a>&nbsp;(' . $count_input . ($new_mail ? '/<span class="red">+' . $new_mail . '</span>' : '') . ')</li>';
//Исходящие сообщения
$count_output = mysql_result(mysql_query("SELECT COUNT(*) FROM `cms_mail` LEFT JOIN `cms_contact` ON `cms_mail`.`from_id`=`cms_contact`.`from_id` AND `cms_contact`.`user_id`='$user_id' 
WHERE `cms_mail`.`user_id`='$user_id' AND `cms_mail`.`delete`!='$user_id' AND `cms_mail`.`sys`='0' AND `cms_contact`.`ban`!='1'"), 0);
//Исходящие непрочитанные сообщения
$count_output_new = mysql_result(mysql_query("SELECT COUNT(*) FROM `cms_mail` LEFT JOIN `cms_contact` ON `cms_mail`.`from_id`=`cms_contact`.`from_id` AND `cms_contact`.`user_id`='$user_id' 
WHERE `cms_mail`.`user_id`='$user_id' AND `cms_mail`.`delete`!='$user_id' AND `cms_mail`.`read`='0' AND `cms_mail`.`sys`='0' AND `cms_contact`.`ban`!='1'"), 0);
echo '<li><a href="../mail/index.php?act=output">' . $lng_profile['sent'] . '</a>&nbsp;(' . $count_output . ($count_output_new ? '/<span class="red">+' . $count_output_new . '</span>' : '') . ')</li>';
$count_systems = mysql_result(mysql_query("SELECT COUNT(*) FROM `cms_mail`
WHERE `from_id`='$user_id' AND `delete`!='$user_id' AND `sys`='1'"), 0);
//Системные сообщения
$count_systems_new = mysql_result(mysql_query("SELECT COUNT(*) FROM `cms_mail`
WHERE `from_id`='$user_id' AND `delete`!='$user_id' AND `sys`='1' AND `read`='0'"), 0);
echo '<li><a href="../mail/index.php?act=systems">' . $lng_profile['systems'] . '</a>&nbsp;(' . $count_systems . ($count_systems_new ? '/<span class="red">+' . $count_systems_new . '</span>' : '') . ')</li>';
//Файлы
$count_file = mysql_result(mysql_query("SELECT COUNT(*) FROM `cms_mail`
WHERE (`user_id`='$user_id' OR `from_id`='$user_id') AND `delete`!='$user_id' AND `file_name`!='';"), 0);
echo '<li><a href="../mail/index.php?act=files">' . $lng['files'] . '</a>&nbsp;(' . $count_file . ')</li>';
if (empty($ban['1']) && empty($ban['3'])) {
    echo '<p><form action="../mail/index.php?act=write" method="post"><input type="submit" value="' . $lng['write'] . '"/></form></p>';
}
// Блок контактов
echo '</ul><h3><img src="../images/contacts.png" width="16" height="16" class="left" />&nbsp;' . $lng['contacts'] . '</h3><ul>';
//Контакты
$count_contacts = mysql_result(mysql_query("SELECT COUNT(*) FROM `cms_contact`
WHERE `user_id`='" . $user_id . "' AND `ban`!='1';"), 0);
echo '<li><a href="../mail/">' . $lng['contacts'] . '</a>&nbsp;(' . $count_contacts . ')</li>';
//Заблокированные
$count_ignor = mysql_result(mysql_query("SELECT COUNT(*) FROM `cms_contact`
WHERE `user_id`='" . $user_id . "' AND `ban`='1';"), 0);
echo '<li><a href="../mail/index.php?act=ignor">' . $lng_profile['banned'] . '</a>&nbsp;(' . $count_ignor . ')</li>';
echo '</ul></p></div>';
// Блок настроек
echo '<div class="bmenu"><p><h3><img src="../images/settings.png" width="16" height="16" class="left" />&#160;' . $lng_profile['my_settings'] . '</h3><ul>' .
    '<li><a href="profile.php?act=settings">' . $lng['system_settings'] . '</a></li>' .
    '<li><a href="profile.php?act=edit">' . $lng_profile['profile_edit'] . '</a></li>' .
    '<li><a href="profile.php?act=password">' . $lng['change_password'] . '</a></li>';
if ($rights >= 1)
    echo '<li><span class="red"><a href="../' . $set['admp'] . '/index.php"><b>' . $lng['admin_panel'] . '</b></a></span></li>';
echo '</ul></p></div>';
?>
