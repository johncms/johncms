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

$title = __('Files');
$nav_chain->add($title);
//Отображаем список файлов
$total = $db->query("SELECT COUNT(*) FROM `cms_mail` WHERE (`user_id`='" . $user->id . "' OR `from_id`='" . $user->id . "') AND `delete`!='" . $user->id . "' AND `file_name`!=''")->fetchColumn();

if ($total) {
    $req = $db->query(
        "SELECT `cms_mail`.*, `users`.`name`, users.lastdate
        FROM `cms_mail`
        LEFT JOIN `users` ON `cms_mail`.`user_id`=`users`.`id`
	    WHERE (`cms_mail`.`user_id`='" . $user->id . "' OR `cms_mail`.`from_id`='" . $user->id . "')
	    AND `cms_mail`.`delete`!='" . $user->id . "'
	    AND `cms_mail`.`file_name`!=''
	    ORDER BY `cms_mail`.`time` DESC
	    LIMIT " . $start . ',' . $user->config->kmess
    );
    $items = [];
    while ($row = $req->fetch()) {
        $row['user_is_online'] = time() <= $row['lastdate'] + 300;
        $row['file_size'] = formatsize($row['size']);
        $items[] = $row;
    }
}

$data['back_url'] = '../profile/?act=office';
$data['total'] = $total;
$data['pagination'] = $tools->displayPagination('?act=files&amp;', $start, $total, $user->config->kmess);
$data['items'] = $items ?? [];

echo $view->render(
    'mail::files',
    [
        'title'      => $title,
        'page_title' => $title,
        'data'       => $data,
    ]
);
