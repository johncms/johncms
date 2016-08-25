<?php

defined('_IN_JOHNCMS') or die('Error: restricted access');

$headmod = 'office';
$textl = _td('My Account');
require('../incfiles/head.php');

// Проверяем права доступа
if ($user['id'] != $user_id) {
    echo functions::display_error(_td('Access forbidden'));
    require('../incfiles/end.php');
    exit;
}

/** @var PDO $db */
$db = App::getContainer()->get(PDO::class);

// Личный кабинет пользователя
$total_photo = $db->query("SELECT COUNT(*) FROM `cms_album_files` WHERE `user_id` = '$user_id'")->fetchColumn();

echo '' .
    '<div class="gmenu"><p><h3>' . _td('My Pages') . '</h3>' .
    '<div>' . functions::image('contacts.png') . '<a href="index.php">' . _td('Profile') . '</a></div>' .
    '<div>' . functions::image('rate.gif') . '<a href="?act=stat">' . _td('Statistics') . '</a></div>' .
    '<div>' . functions::image('photo.gif') . '<a href="album.php?act=list">' . _td('Photo Album') . '</a>&#160;(' . $total_photo . ')</div>' .
    '<div>' . functions::image('guestbook.gif') . '<a href="?act=guestbook">' . _td('Guestbook') . '</a>&#160;(' . $user['comm_count'] . ')</div>';

if ($rights >= 1) {
    $guest = counters::guestbook(2);
    echo '<div>' . functions::image('forbidden.png') . '<a href="../guestbook/index.php?act=ga&amp;do=set">' . _td('Admin-Club') . '</a> (<span class="red">' . $guest . '</span>)</div>';
}
echo '</p></div>';

// Блок почты
echo '<div class="list2"><p><h3>' . _td('My Mailbox') . '</h3>';

//Входящие сообщения
$count_input = $db->query("
	SELECT COUNT(*) 
	FROM `cms_mail` 
	LEFT JOIN `cms_contact` 
	ON `cms_mail`.`user_id`=`cms_contact`.`from_id` 
	AND `cms_contact`.`user_id`='$user_id' 
	WHERE `cms_mail`.`from_id`='$user_id' 
	AND `cms_mail`.`sys`='0' AND `cms_mail`.`delete`!='$user_id' 
	AND `cms_contact`.`ban`!='1' AND `spam`='0'")->fetchColumn();
echo '<div>' . functions::image('mail-inbox.png') . '<a href="../mail/index.php?act=input">' . _td('Received') . '</a>&nbsp;(' . $count_input . ($new_mail ? '/<span class="red">+' . $new_mail . '</span>' : '') . ')</div>';

//Исходящие сообщения
$count_output = $db->query("SELECT COUNT(*) FROM `cms_mail` LEFT JOIN `cms_contact` ON `cms_mail`.`from_id`=`cms_contact`.`from_id` AND `cms_contact`.`user_id`='$user_id' 
WHERE `cms_mail`.`user_id`='$user_id' AND `cms_mail`.`delete`!='$user_id' AND `cms_mail`.`sys`='0' AND `cms_contact`.`ban`!='1'")->fetchColumn();

//Исходящие непрочитанные сообщения
$count_output_new = $db->query("SELECT COUNT(*) FROM `cms_mail` LEFT JOIN `cms_contact` ON `cms_mail`.`from_id`=`cms_contact`.`from_id` AND `cms_contact`.`user_id`='$user_id' 
WHERE `cms_mail`.`user_id`='$user_id' AND `cms_mail`.`delete`!='$user_id' AND `cms_mail`.`read`='0' AND `cms_mail`.`sys`='0' AND `cms_contact`.`ban`!='1'")->fetchColumn();
echo '<div>' . functions::image('mail-send.png') . '<a href="../mail/index.php?act=output">' . _td('Sent') . '</a>&nbsp;(' . $count_output . ($count_output_new ? '/<span class="red">+' . $count_output_new . '</span>' : '') . ')</div>';
$count_systems = $db->query("SELECT COUNT(*) FROM `cms_mail` WHERE `from_id`='$user_id' AND `delete`!='$user_id' AND `sys`='1'")->fetchColumn();

//Системные сообщения
$count_systems_new = $db->query("SELECT COUNT(*) FROM `cms_mail` WHERE `from_id`='$user_id' AND `delete`!='$user_id' AND `sys`='1' AND `read`='0'")->fetchColumn();
echo '<div>' . functions::image('mail-info.png') . '<a href="../mail/index.php?act=systems">' . _td('System') . '</a>&nbsp;(' . $count_systems . ($count_systems_new ? '/<span class="red">+' . $count_systems_new . '</span>' : '') . ')</div>';

//Файлы
$count_file = $db->query("SELECT COUNT(*) FROM `cms_mail` WHERE (`user_id`='$user_id' OR `from_id`='$user_id') AND `delete`!='$user_id' AND `file_name`!='';")->fetchColumn();
echo '<div>' . functions::image('file.gif') . '<a href="../mail/index.php?act=files">' . _td('Files') . '</a>&nbsp;(' . $count_file . ')</div>';

if (empty($ban['1']) && empty($ban['3'])) {
    echo '<p><form action="../mail/index.php?act=write" method="post"><input type="submit" value="' . _td('Write') . '"/></form></p>';
}

// Блок контактов
echo '</p></div><div class="menu"><p><h3>' . _td('Contacts') . '</h3>';

//Контакты
$count_contacts = $db->query("SELECT COUNT(*) FROM `cms_contact` WHERE `user_id`='" . $user_id . "' AND `ban`!='1'")->fetchColumn();
echo '<div>' . functions::image('user.png') . '<a href="../mail/">' . _td('Contacts') . '</a>&nbsp;(' . $count_contacts . ')</div>';

//Заблокированные
$count_ignor = $db->query("SELECT COUNT(*) FROM `cms_contact` WHERE `user_id`='" . $user_id . "' AND `ban`='1'")->fetchColumn();
echo '<div>' . functions::image('user-block.png') . '<a href="../mail/index.php?act=ignor">' . _td('Blocked') . '</a>&nbsp;(' . $count_ignor . ')</div>';
echo '</p></div>';

// Блок настроек
echo '<div class="bmenu"><p><h3>' . _td('Settings') . '</h3>' .
    '<div>' . functions::image('user-edit.png') . '<a href="?act=edit">' . _td('Edit Profile') . '</a></div>' .
    '<div>' . functions::image('lock.png') . '<a href="?act=password">' . _td('Change Password') . '</a></div>' .
    '<div>' . functions::image('settings.png') . '<a href="?act=settings">' . _td('System Settings') . '</a></div>';
if ($rights >= 1) {
    echo '<div>' . functions::image('forbidden.png') . '<span class="red"><a href="../admin/"><b>' . _td('Admin Panel') . '</b></a></span></div>';
}
echo '</p></div>';

// Выход с сайта
echo '<div class="rmenu"><p><a href="' . $set['homeurl'] . '/exit.php">' . functions::image('del.png') . _td('Exit') . '</a></p></div>';
