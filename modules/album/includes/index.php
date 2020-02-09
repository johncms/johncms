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

$data['men'] = $db->query('SELECT COUNT(DISTINCT(`t1`.`user_id`))
FROM `cms_album_cat` t1
JOIN `users` u ON `u`.`id` = `t1`.`user_id` WHERE `u`.`sex` = "m" ' . ($user->rights >= 6 ? '' : ' AND (`access` > 1 OR `user_id` = ' . $user->id . ')'))->fetchColumn();

$data['women'] = $db->query('SELECT COUNT(DISTINCT(`t1`.`user_id`))
FROM `cms_album_cat` t1
JOIN `users` u ON `u`.`id` = `t1`.`user_id` WHERE `u`.`sex` = "zh" ' . ($user->rights >= 6 ? '' : ' AND (`access` > 1 OR `user_id` = ' . $user->id . ')'))->fetchColumn();

$data['albums'] = ($data['men'] + $data['women']);

$data['new'] = $db->query("SELECT COUNT(*) FROM `cms_album_files` WHERE `time` > '" . (time() - 259200) . "' AND `access` = '4'")->fetchColumn();

echo $view->render(
    'album::index',
    [
        'title'      => $title,
        'page_title' => $title,
        'data'       => $data,
    ]
);
