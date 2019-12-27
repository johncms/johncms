<?php

declare(strict_types=1);

/*
 * This file is part of JohnCMS Content Management System.
 *
 * @copyright JohnCMS Community
 * @license   https://opensource.org/licenses/GPL-3.0 GPL-3.0
 * @link      https://johncms.com JohnCMS Project
 */

defined('_IN_JOHNCMS') || die('Error: restricted access');

$textl = _t('My Account');

// Проверяем права доступа
if ($foundUser['id'] != $user->id) {
    echo $view->render('system::app/old_content', [
        'title'   => $textl,
        'content' => $tools->displayError(_t('Access forbidden')),
    ]);
    exit;
}

// Личный кабинет пользователя
$total_photo = $db->query("SELECT COUNT(*) FROM `cms_album_files` WHERE `user_id` = '" . $user->id . "'")->fetchColumn();

echo '' .
    '<div class="gmenu"><p><h3>' . _t('My Pages') . '</h3>' .
    '<div><img src="' . $assets->url('images/old/contacts.png') . '" alt="" class="icon"><a href="./">' . _t('Profile') . '</a></div>' .
    '<div><img src="' . $assets->url('images/old/rate.gif') . '" alt="" class="icon"><a href="?act=stat">' . _t('Statistics') . '</a></div>' .
    '<div><img src="' . $assets->url('images/old/photo.gif') . '" alt="" class="icon"><a href="../album/?act=list">' . _t('Photo Album') . '</a>&#160;(' . $total_photo . ')</div>' .
    '<div><img src="' . $assets->url('images/old/guestbook.gif') . '" alt="" class="icon"><a href="?act=guestbook">' . _t('Guestbook') . '</a>&#160;(' . $user['comm_count'] . ')</div>';

if ($user->rights >= 1) {
    $guest = di('counters')->guestbook(2);
    echo '<div><img src="' . $assets->url('images/old/forbidden.png') . '" alt="" class="icon"><a href="../guestbook/?act=ga&amp;do=set">' . _t('Admin-Club') . '</a> (<span class="red">' . $guest . '</span>)</div>';
}
echo '</p></div>';

// Блок почты
echo '<div class="list2"><p><h3>' . _t('My Mailbox') . '</h3>';

//TODO: Перенести данный запрос в счетчики и использовать результат вместе с уведомлениями
$new_mail = $db->query("SELECT COUNT(*) FROM `cms_mail`
  LEFT JOIN `cms_contact` ON `cms_mail`.`user_id`=`cms_contact`.`from_id` AND `cms_contact`.`user_id`='" . $user->id . "'
  WHERE `cms_mail`.`from_id`='" . $user->id . "'
  AND `cms_mail`.`sys`='0'
  AND `cms_mail`.`read`='0'
  AND `cms_mail`.`delete`!='" . $user->id . "'
  AND `cms_contact`.`ban`!='1'")->fetchColumn();

//Входящие сообщения
$count_input = $db->query("
	SELECT COUNT(*)
	FROM `cms_mail`
	LEFT JOIN `cms_contact`
	ON `cms_mail`.`user_id`=`cms_contact`.`from_id`
	AND `cms_contact`.`user_id`='" . $user->id . "'
	WHERE `cms_mail`.`from_id`='" . $user->id . "'
	AND `cms_mail`.`sys`='0' AND `cms_mail`.`delete`!='" . $user->id . "'
	AND `cms_contact`.`ban`!='1' AND `spam`='0'")->fetchColumn();
echo '<div><img src="' . $assets->url('images/old/mail-inbox.png') . '" alt="" class="icon"><a href="../mail/?act=input">' . _t('Received') . '</a>&nbsp;(' . $count_input . ($new_mail ? '/<span class="red">+' . $new_mail . '</span>' : '') . ')</div>'; // phpcs:ignore

//Исходящие сообщения
$count_output = $db->query("SELECT COUNT(*) FROM `cms_mail` LEFT JOIN `cms_contact` ON `cms_mail`.`from_id`=`cms_contact`.`from_id` AND `cms_contact`.`user_id`='" . $user->id . "'
WHERE `cms_mail`.`user_id`='" . $user->id . "' AND `cms_mail`.`delete`!='" . $user->id . "' AND `cms_mail`.`sys`='0' AND `cms_contact`.`ban`!='1'")->fetchColumn();

//Исходящие непрочитанные сообщения
$count_output_new = $db->query("SELECT COUNT(*) FROM `cms_mail` LEFT JOIN `cms_contact` ON `cms_mail`.`from_id`=`cms_contact`.`from_id` AND `cms_contact`.`user_id`='" . $user->id . "'
WHERE `cms_mail`.`user_id`='" . $user->id . "' AND `cms_mail`.`delete`!='" . $user->id . "' AND `cms_mail`.`read`='0' AND `cms_mail`.`sys`='0' AND `cms_contact`.`ban`!='1'")->fetchColumn();
echo '<div><img src="' . $assets->url('images/old/mail-send.png') . '" alt="" class="icon"><a href="../mail/?act=output">' . _t('Sent') . '</a>&nbsp;(' . $count_output . ($count_output_new ? '/<span class="red">+' . $count_output_new . '</span>' : '') . ')</div>'; // phpcs:ignore
$count_systems = $db->query("SELECT COUNT(*) FROM `cms_mail` WHERE `from_id`='" . $user->id . "' AND `delete`!='" . $user->id . "' AND `sys`='1'")->fetchColumn();

//Системные сообщения
$count_systems_new = $db->query("SELECT COUNT(*) FROM `cms_mail` WHERE `from_id`='" . $user->id . "' AND `delete`!='" . $user->id . "' AND `sys`='1' AND `read`='0'")->fetchColumn();
echo '<div><img src="' . $assets->url('images/old/mail-info.png') . '" alt="" class="icon"><a href="../mail/?act=systems">' . _t('System') . '</a>&nbsp;(' . $count_systems . ($count_systems_new ? '/<span class="red">+' . $count_systems_new . '</span>' : '') . ')</div>'; // phpcs:ignore

//Файлы
$count_file = $db->query("SELECT COUNT(*) FROM `cms_mail` WHERE (`user_id`='" . $user->id . "' OR `from_id`='" . $user->id . "') AND `delete`!='" . $user->id . "' AND `file_name`!='';")->fetchColumn();
echo '<div><img src="' . $assets->url('images/old/file.gif') . '" alt="" class="icon"><a href="../mail/?act=files">' . _t('Files') . '</a>&nbsp;(' . $count_file . ')</div>';

if (! isset($user->ban['1']) && ! isset($user->ban['3'])) {
    echo '<p><form action="../mail/?act=write" method="post"><input type="submit" value="' . _t('Write') . '"/></form></p>';
}

// Блок контактов
echo '</p></div><div class="menu"><p><h3>' . _t('Contacts') . '</h3>';

//Контакты
$count_contacts = $db->query("SELECT COUNT(*) FROM `cms_contact` WHERE `user_id`='" . $user->id . "' AND `ban`!='1'")->fetchColumn();
echo '<div><img src="' . $assets->url('images/old/user.png') . '" alt="" class="icon"><a href="../mail/">' . _t('Contacts') . '</a>&nbsp;(' . $count_contacts . ')</div>';

//Заблокированные
$count_ignor = $db->query("SELECT COUNT(*) FROM `cms_contact` WHERE `user_id`='" . $user->id . "' AND `ban`='1'")->fetchColumn();
echo '<div><img src="' . $assets->url('images/old/user-block.png') . '" alt="" class="icon"><a href="../mail/?act=ignor">' . _t('Blocked') . '</a>&nbsp;(' . $count_ignor . ')</div>';
echo '</p></div>';

// Блок настроек
echo '<div class="bmenu"><p><h3>' . _t('Settings') . '</h3>' .
    '<div><img src="' . $assets->url('images/old/user-edit.png') . '" alt="" class="icon"><a href="?act=edit">' . _t('Edit Profile') . '</a></div>' .
    '<div><img src="' . $assets->url('images/old/lock.png') . '" alt="" class="icon"><a href="?act=password">' . _t('Change Password') . '</a></div>' .
    '<div><img src="' . $assets->url('images/old/settings.png') . '" alt="" class="icon"><a href="?act=settings">' . _t('System Settings') . '</a></div>';
echo '</p></div>';

// Выход с сайта
echo '<div class="rmenu padding"><img src="' . $assets->url('images/old/del.png') . '" alt="" class="icon"><a href="' . $config['homeurl'] . '/login">' . _t('Exit') . '</a></div>';
