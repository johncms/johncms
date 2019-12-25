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

$data = [];

$data['albums'] = $db->query('SELECT COUNT(DISTINCT `user_id`) FROM `cms_album_files`')->fetchColumn();
$data['men'] = $db->query(
    "SELECT COUNT(DISTINCT `user_id`)
      FROM `cms_album_files`
      LEFT JOIN `users` ON `cms_album_files`.`user_id` = `users`.`id`
      WHERE `users`.`sex` = 'm'
    "
)->fetchColumn();

$data['women'] = $db->query(
    "SELECT COUNT(DISTINCT `user_id`)
      FROM `cms_album_files`
      LEFT JOIN `users` ON `cms_album_files`.`user_id` = `users`.`id`
      WHERE `users`.`sex` = 'zh'
    "
)->fetchColumn();

$data['new'] = $db->query("SELECT COUNT(*) FROM `cms_album_files` WHERE `time` > '" . (time() - 259200) . "' AND `access` > '1'")->fetchColumn();

echo $view->render(
    'album::index',
    [
        'title'      => $title,
        'page_title' => $title,
        'data'       => $data,
    ]
);
