<?php

/*
 * This file is part of JohnCMS Content Management System.
 *
 * @copyright JohnCMS Community
 * @license   https://opensource.org/licenses/GPL-3.0 GPL-3.0
 * @link      https://johncms.com JohnCMS Project
 */

declare(strict_types=1);

defined('_IN_JOHNCMS') || die('Error: restricted access');

$req = $db->query(
    '
              SELECT `cms_library_comments`.`user_id`, `cms_library_comments`.`text`, `library_texts`.`name`, `library_texts`.`comm_count`, `library_texts`.`id`, `cms_library_comments`.`time`
                FROM `cms_library_comments`
              JOIN `library_texts` ON `cms_library_comments`.`sub_id` = `library_texts`.`id`
                GROUP BY `library_texts`.`id`
                ORDER BY `cms_library_comments`.`time` DESC
                LIMIT 20
                '
);

$total = $req->rowCount();

echo $view->render(
    'library::lastcom',
    [
        'total' => $total,
        'list'  =>
            static function () use ($req, $tools, $db) {
                while ($res = $req->fetch()) {
                    $res['text'] = $tools->checkout(substr($res['text'], 0, 500), 0, 2);
                    $res['image'] = file_exists(UPLOAD_PATH . 'library/images/small/' . $res['id'] . '.png');
                    $res['name'] = $tools->checkout($res['name']);
                    $res['who'] = $tools->checkout($db->query('SELECT `name` FROM `users` WHERE `id` = ' . $res['user_id'])->fetchColumn()) . ' (' . $tools->displayDate($res['time']) . ')';

                    yield $res;
                }
            },
    ]
);
