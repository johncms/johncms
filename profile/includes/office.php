<?php
/*
 * JohnCMS NEXT Mobile Content Management System (http://johncms.com)
 *
 * For copyright and license information, please see the LICENSE.md
 * Installing the system or redistributions of files must retain the above copyright notice.
 *
 * @link        http://johncms.com JohnCMS Project
 * @copyright   Copyright (C) JohnCMS Community
 * @license     GPL-3
 */

defined('_IN_JOHNCMS') or die('Error: restricted access');

$headmod = 'office';
$textl = _t('My Account');
require('../system/head.php');

/** @var Psr\Container\ContainerInterface $container */
$container = App::getContainer();

/** @var PDO $db */
$db = $container->get(PDO::class);

/** @var Johncms\Api\UserInterface $systemUser */
$systemUser = $container->get(Johncms\Api\UserInterface::class);

/** @var Johncms\Api\ToolsInterface $tools */
$tools = $container->get(Johncms\Api\ToolsInterface::class);

// Проверяем права доступа
if ($user['id'] != $systemUser->id) {
    echo $tools->displayError(_t('Access forbidden'));
    require('../system/end.php');
    exit;
}

/** @var Johncms\Api\ConfigInterface $config */
$config = $container->get(Johncms\Api\ConfigInterface::class);

// Личный кабинет пользователя
$total_photo = $db->query("SELECT COUNT(*) FROM `cms_album_files` WHERE `user_id` = '" . $systemUser->id . "'")->fetchColumn();

echo '' .
    '<div class="gmenu"><p><h3>' . _t('My Pages') . '</h3>' .
    '<div>' . $tools->image('contacts.png') . '<a href="index.php">' . _t('Profile') . '</a></div>' .
    '<div>' . $tools->image('rate.gif') . '<a href="?act=stat">' . _t('Statistics') . '</a></div>' .
    '<div>' . $tools->image('photo.gif') . '<a href="../album/index.php?act=list">' . _t('Photo Album') . '</a>&#160;(' . $total_photo . ')</div>' .
    '<div>' . $tools->image('guestbook.gif') . '<a href="?act=guestbook">' . _t('Guestbook') . '</a>&#160;(' . $user['comm_count'] . ')</div>';

if ($systemUser->rights >= 1) {
    $guest = $container->get('counters')->guestbook(2);
    echo '<div>' . $tools->image('forbidden.png') . '<a href="../guestbook/index.php?act=ga&amp;do=set">' . _t('Admin-Club') . '</a> (<span class="red">' . $guest . '</span>)</div>';
}
echo '</p></div>';

// Блок почты
echo '<div class="list2"><p><h3>' . _t('My Mailbox') . '</h3>';

//Входящие сообщения
$count_input = $db->query("
	SELECT COUNT(*) 
	FROM `cms_mail` 
	LEFT JOIN `cms_contact` 
	ON `cms_mail`.`user_id`=`cms_contact`.`from_id` 
	AND `cms_contact`.`user_id`='" . $systemUser->id . "' 
	WHERE `cms_mail`.`from_id`='" . $systemUser->id . "' 
	AND `cms_mail`.`sys`='0' AND `cms_mail`.`delete`!='" . $systemUser->id . "' 
	AND `cms_contact`.`ban`!='1' AND `spam`='0'")->fetchColumn();
echo '<div>' . $tools->image('mail-inbox.png') . '<a href="../mail/index.php?act=input">' . _t('Received') . '</a>&nbsp;(' . $count_input . ($new_mail ? '/<span class="red">+' . $new_mail . '</span>' : '') . ')</div>';

//Исходящие сообщения
$count_output = $db->query("SELECT COUNT(*) FROM `cms_mail` LEFT JOIN `cms_contact` ON `cms_mail`.`from_id`=`cms_contact`.`from_id` AND `cms_contact`.`user_id`='" . $systemUser->id . "' 
WHERE `cms_mail`.`user_id`='" . $systemUser->id . "' AND `cms_mail`.`delete`!='" . $systemUser->id . "' AND `cms_mail`.`sys`='0' AND `cms_contact`.`ban`!='1'")->fetchColumn();

//Исходящие непрочитанные сообщения
$count_output_new = $db->query("SELECT COUNT(*) FROM `cms_mail` LEFT JOIN `cms_contact` ON `cms_mail`.`from_id`=`cms_contact`.`from_id` AND `cms_contact`.`user_id`='" . $systemUser->id . "' 
WHERE `cms_mail`.`user_id`='" . $systemUser->id . "' AND `cms_mail`.`delete`!='" . $systemUser->id . "' AND `cms_mail`.`read`='0' AND `cms_mail`.`sys`='0' AND `cms_contact`.`ban`!='1'")->fetchColumn();
echo '<div>' . $tools->image('mail-send.png') . '<a href="../mail/index.php?act=output">' . _t('Sent') . '</a>&nbsp;(' . $count_output . ($count_output_new ? '/<span class="red">+' . $count_output_new . '</span>' : '') . ')</div>';
$count_systems = $db->query("SELECT COUNT(*) FROM `cms_mail` WHERE `from_id`='" . $systemUser->id . "' AND `delete`!='" . $systemUser->id . "' AND `sys`='1'")->fetchColumn();

//Системные сообщения
$count_systems_new = $db->query("SELECT COUNT(*) FROM `cms_mail` WHERE `from_id`='" . $systemUser->id . "' AND `delete`!='" . $systemUser->id . "' AND `sys`='1' AND `read`='0'")->fetchColumn();
echo '<div>' . $tools->image('mail-info.png') . '<a href="../mail/index.php?act=systems">' . _t('System') . '</a>&nbsp;(' . $count_systems . ($count_systems_new ? '/<span class="red">+' . $count_systems_new . '</span>' : '') . ')</div>';

//Файлы
$count_file = $db->query("SELECT COUNT(*) FROM `cms_mail` WHERE (`user_id`='" . $systemUser->id . "' OR `from_id`='" . $systemUser->id . "') AND `delete`!='" . $systemUser->id . "' AND `file_name`!='';")->fetchColumn();
echo '<div>' . $tools->image('file.gif') . '<a href="../mail/index.php?act=files">' . _t('Files') . '</a>&nbsp;(' . $count_file . ')</div>';

if (!isset($systemUser->ban['1']) && !isset($systemUser->ban['3'])) {
    echo '<p><form action="../mail/index.php?act=write" method="post"><input type="submit" value="' . _t('Write') . '"/></form></p>';
}

// Блок контактов
echo '</p></div><div class="menu"><p><h3>' . _t('Contacts') . '</h3>';

//Контакты
$count_contacts = $db->query("SELECT COUNT(*) FROM `cms_contact` WHERE `user_id`='" . $systemUser->id . "' AND `ban`!='1'")->fetchColumn();
echo '<div>' . $tools->image('user.png') . '<a href="../mail/">' . _t('Contacts') . '</a>&nbsp;(' . $count_contacts . ')</div>';

//Заблокированные
$count_ignor = $db->query("SELECT COUNT(*) FROM `cms_contact` WHERE `user_id`='" . $systemUser->id . "' AND `ban`='1'")->fetchColumn();
echo '<div>' . $tools->image('user-block.png') . '<a href="../mail/index.php?act=ignor">' . _t('Blocked') . '</a>&nbsp;(' . $count_ignor . ')</div>';
echo '</p></div>';

// Блок настроек
echo '<div class="bmenu"><p><h3>' . _t('Settings') . '</h3>' .
    '<div>' . $tools->image('user-edit.png') . '<a href="?act=edit">' . _t('Edit Profile') . '</a></div>' .
    '<div>' . $tools->image('lock.png') . '<a href="?act=password">' . _t('Change Password') . '</a></div>' .
    '<div>' . $tools->image('settings.png') . '<a href="?act=settings">' . _t('System Settings') . '</a></div>';
if ($systemUser->rights >= 1) {
    echo '<div>' . $tools->image('forbidden.png') . '<span class="red"><a href="../admin/"><b>' . _t('Admin Panel') . '</b></a></span></div>';
}
echo '</p></div>';

// Выход с сайта
echo '<div class="rmenu"><p><a href="' . $config['homeurl'] . '/exit.php">' . $tools->image('del.png') . _t('Exit') . '</a></p></div>';
