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

$title = __('Latest comments');
$nav_chain->add($title);

$req = $db->query('SELECT
    `comm`.`user_id`,
    `comm`.`text`,
    `txt`.`name`,
    `txt`.`comm_count`,
    `txt`.`id`,
    `comm`.`time`,
    u.`name` AS user_name
FROM `cms_library_comments` comm
JOIN `library_texts` txt ON `comm`.`sub_id` = `txt`.`id`
JOIN `users` u ON u.`id` = `comm`.`user_id`
JOIN (
SELECT `sub_id`, max(`time`) as `mtime` FROM `cms_library_comments` GROUP BY `sub_id`) as comm2
ON comm.`sub_id`= comm2.`sub_id` AND comm.`time` = comm2.`mtime`
ORDER BY `comm`.`time` DESC LIMIT 20');

$total = $req->rowCount();

echo $view->render(
    'library::lastcom',
    [
        'title'      => $title,
        'page_title' => $page_title ?? $title,
        'total'      => $total,
        'list'       =>
            static function () use ($req, $tools, $db) {
                while ($res = $req->fetch()) {
                    $res['text'] = $tools->checkout(substr($res['text'], 0, 500), 0, 2);
                    $res['image'] = file_exists(UPLOAD_PATH . 'library/images/small/' . $res['id'] . '.png');
                    $res['name'] = $tools->checkout($res['name']);
                    $res['who'] = $tools->checkout($res['user_name']) . ' (' . $tools->displayDate($res['time']) . ')';

                    yield $res;
                }
            },
    ]
);
