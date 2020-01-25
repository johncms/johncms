<?php

/**
 * This file is part of JohnCMS Content Management System.
 *
 * @copyright JohnCMS Community
 * @license   https://opensource.org/licenses/GPL-3.0 GPL-3.0
 * @link      https://johncms.com JohnCMS Project
 */

declare(strict_types=1);

use Johncms\UserProperties;

defined('_IN_JOHNADM') || die('Error: restricted access');

/**
 * @var PDO $db
 * @var Johncms\System\Legacy\Tools $tools
 * @var Johncms\System\Users\User $user
 */

$mod = isset($_GET['mod']) ? trim($_GET['mod']) : '';
$title = __('Ban Panel');
$nav_chain->add($title);


switch ($mod) {
    case 'amnesty':
        if ($user->rights < 9) {
            echo $view->render(
                'system::pages/result',
                [
                    'title'    => $title,
                    'type'     => 'alert-danger',
                    'message'  => __('Amnesty is available for supervisors only'),
                    'back_url' => '/admin/ban_panel/',
                ]
            );
        } else {
            $title = __('Amnesty');

            if (isset($_POST['submit'])) {
                $term = isset($_POST['term']) && $_POST['term'] == 1 ? 1 : 0;

                if ($term) {
                    // Очищаем таблицу Банов
                    $db->query('TRUNCATE TABLE `cms_ban_users`');
                    echo $view->render(
                        'system::pages/result',
                        [
                            'title'    => $title,
                            'type'     => 'alert-success',
                            'message'  => __('Amnesty has been successful'),
                            'back_url' => '/admin/ban_panel/',
                        ]
                    );
                } else {
                    // Разбаниваем активные Баны
                    $req = $db->query("SELECT * FROM `cms_ban_users` WHERE `ban_time` > '" . time() . "'");

                    while ($res = $req->fetch()) {
                        $ban_left = $res['ban_time'] - time();

                        if ($ban_left < 2592000) {
                            $amnesty_msg = __('Amnesty');
                            $db->exec("UPDATE `cms_ban_users` SET `ban_time`='" . time() . "', `ban_raz`='--${amnesty_msg}--' WHERE `id` = '" . $res['id'] . "'");
                        }
                    }
                    echo $view->render(
                        'system::pages/result',
                        [
                            'title'    => $title,
                            'type'     => 'alert-success',
                            'message'  => __('All the users with active bans were unbanned (Except for bans &quot;till cancel&quot;)'),
                            'back_url' => '/admin/ban_panel/',
                        ]
                    );
                }
            } else {
                echo $view->render(
                    'admin::amnesty',
                    [
                        'title'      => $title,
                        'page_title' => $title,
                    ]
                );
            }
        }
        break;

    default:
        // БАН-панель, список нарушителей
        $data['filters'] = [
            [
                'url'    => '?',
                'name'   => __('Term'),
                'active' => ! isset($_GET['count']),
            ],
            [
                'url'    => '?count',
                'name'   => __('Violations'),
                'active' => isset($_GET['count']),
            ],
        ];

        $sort = isset($_GET['count']) ? 'bancount' : 'bantime';
        $total = $db->query('SELECT COUNT(DISTINCT (`user_id`)) FROM `cms_ban_users`')->fetchColumn();

        $req = $db->query('SELECT
    `ban`.`id` AS `ban_id`,
    `ban`.`ban_time` AS `bantime`,
    `u`.`id`,
    `u`.`name`,
    `u`.`lastdate`,
    `u`.`ip`,
    `u`.`ip_via_proxy`,
    `u`.`browser`, (
    SELECT COUNT(*) FROM `cms_ban_users` WHERE `user_id` = `u`.`id`) AS `bancount`
FROM `cms_ban_users` ban
LEFT JOIN `users` u ON `ban`.`user_id` = `u`.`id`
JOIN (
SELECT `user_id`, MAX(`ban_time`) `mtime` FROM `cms_ban_users` GROUP BY `user_id`) AS tmp
ON `tmp`.`user_id` = `ban`.`user_id` AND `tmp`.`mtime` = `ban`.`ban_time`
ORDER BY `' . $sort . '` DESC LIMIT ' . $start . ',' . $user->config->kmess);

        if ($req->rowCount()) {
            $items = [];
            while ($res = $req->fetch()) {
                $res['buttons'] = [
                    [
                        'url'  => '/profile/?act=ban&amp;mod=ban&amp;user=' . $res['id'],
                        'name' => __('Violations history') . ' (' . $res['bancount'] . ')',
                    ],
                ];

                $res['active'] = $res['bantime'] > time();
                $res['user_id'] = $res['id'];
                $user_properties = new UserProperties();
                $user_data = $user_properties->getFromArray($res);
                $res = array_merge($res, $user_data);
                $items[] = $res;
            }
        }

        if ($total > $user->config->kmess) {
            $data['pagination'] = $tools->displayPagination('?act=ban_panel&amp;', $start, $total, $user->config->kmess);
        }

        $data['total'] = $total;
        $data['items'] = $items ?? [];
        $data['back_url'] = '/admin/';

        echo $view->render(
            'admin::ban_panel',
            [
                'title'      => $title,
                'page_title' => $title,
                'data'       => $data,
            ]
        );
}
