<?php

/**
 * This file is part of JohnCMS Content Management System.
 *
 * @copyright JohnCMS Community
 * @license   https://opensource.org/licenses/GPL-3.0 GPL-3.0
 * @link      https://johncms.com JohnCMS Project
 */

declare(strict_types=1);

defined('_IN_JOHNCMS') || die('Error: restricted access');

$title = __('My Account');
$nav_chain->add($title);
// Проверяем права доступа
if ($user_data->id !== $user->id) {
    echo $view->render(
        'system::pages/result',
        [
            'title'   => $title,
            'type'    => 'alert-danger',
            'message' => __('Access forbidden'),
        ]
    );
    exit;
}

// Личный кабинет пользователя
$total_photo = $db->query("SELECT COUNT(*) FROM `cms_album_files` WHERE `user_id` = '" . $user->id . "'")->fetchColumn();

$new_mail = $db->query(
    "SELECT COUNT(*) FROM `cms_mail`
  LEFT JOIN `cms_contact` ON `cms_mail`.`user_id`=`cms_contact`.`from_id` AND `cms_contact`.`user_id`='" . $user->id . "'
  WHERE `cms_mail`.`from_id`='" . $user->id . "'
  AND `cms_mail`.`sys`='0'
  AND `cms_mail`.`read`='0'
  AND `cms_mail`.`delete`!='" . $user->id . "'
  AND `cms_contact`.`ban`!='1'"
)->fetchColumn();

$count_input = $db->query(
    "SELECT COUNT(*)
	FROM `cms_mail`
	LEFT JOIN `cms_contact`
	ON `cms_mail`.`user_id`=`cms_contact`.`from_id`
	AND `cms_contact`.`user_id`='" . $user->id . "'
	WHERE `cms_mail`.`from_id`='" . $user->id . "'
	AND `cms_mail`.`sys`='0' AND `cms_mail`.`delete`!='" . $user->id . "'
	AND `cms_contact`.`ban`!='1' AND `spam`='0'"
)->fetchColumn();

//Исходящие сообщения
$count_output = $db->query(
    "SELECT COUNT(*) FROM `cms_mail` LEFT JOIN `cms_contact` ON `cms_mail`.`from_id`=`cms_contact`.`from_id` AND `cms_contact`.`user_id`='" . $user->id . "'
WHERE `cms_mail`.`user_id`='" . $user->id . "' AND `cms_mail`.`delete`!='" . $user->id . "' AND `cms_mail`.`sys`='0' AND `cms_contact`.`ban`!='1'"
)->fetchColumn();

//Исходящие непрочитанные сообщения
$count_output_new = $db->query(
    "SELECT COUNT(*) FROM `cms_mail` LEFT JOIN `cms_contact` ON `cms_mail`.`from_id`=`cms_contact`.`from_id` AND `cms_contact`.`user_id`='" . $user->id . "'
WHERE `cms_mail`.`user_id`='" . $user->id . "' AND `cms_mail`.`delete`!='" . $user->id . "' AND `cms_mail`.`read`='0' AND `cms_mail`.`sys`='0' AND `cms_contact`.`ban`!='1'"
)->fetchColumn();

//Контакты
$count_contacts = $db->query("SELECT COUNT(*) FROM `cms_contact` WHERE `user_id`='" . $user->id . "' AND `ban`!='1'")->fetchColumn();

//Файлы
$count_file = $db->query("SELECT COUNT(*) FROM `cms_mail` WHERE (`user_id`='" . $user->id . "' OR `from_id`='" . $user->id . "') AND `delete`!='" . $user->id . "' AND `file_name`!='';")->fetchColumn();

//Заблокированные
$count_ignor = $db->query("SELECT COUNT(*) FROM `cms_contact` WHERE `user_id`='" . $user->id . "' AND `ban`='1'")->fetchColumn();

$data = [
    'counters' => [
        'total_photo'      => $total_photo,
        'inbox'            => $count_input,
        'new_messages'     => $new_mail,
        'outbox'           => $count_output,
        'unread_sent'      => $count_output_new,
        'files'            => $count_file,
        'contacts'         => $count_contacts,
        'blocked_contacts' => $count_ignor,
    ],
];

echo $view->render(
    'profile::office',
    [
        'title'      => $title,
        'page_title' => $title,
        'data'       => $data,
    ]
);
