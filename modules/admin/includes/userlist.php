<?php

declare(strict_types=1);

/*
 * This file is part of JohnCMS Content Management System.
 *
 * @copyright JohnCMS Community
 * @license   https://opensource.org/licenses/GPL-3.0 GPL-3.0
 * @link      https://johncms.com JohnCMS Project
 */

use Johncms\NavChain;

defined('_IN_JOHNADM') || die('Error: restricted access');

/**
 * @var PDO $db
 * @var Johncms\System\Legacy\Tools $tools
 */

/** @var NavChain $navChain */
$navChain = di(NavChain::class);
$navChain->add(__('Admin Panel'), '../');
$navChain->add(__('List of Users'));

$sort = isset($_GET['sort']) ? trim($_GET['sort']) : '';

switch ($sort) {
    case 'nick':
        $sort = 'nick';
        $order = '`name` ASC';
        break;

    case 'ip':
        $sort = 'ip';
        $order = '`ip` ASC';
        break;

    default:
        $sort = 'id';
        $order = '`id` ASC';
}

$total = $db->query('SELECT COUNT(*) FROM `users`')->fetchColumn();
$req = $db->query("SELECT * FROM `users` WHERE `preg` = 1 ORDER BY ${order} LIMIT ${start}, " . $user->config->kmess);

echo $view->render(
    'admin::userlist',
    [
        'pagination' => $tools->displayPagination('?sort=' . $sort . '&amp;', $start, $total, $user->config->kmess),
        'sort'       => $sort,
        'total'      => $total,
        'list'       => function () use ($req, $user) {
            while ($res = $req->fetch()) {
                $res['user_profile_link'] = '';
                if (! empty($res['id']) && $user->id !== $res['id'] && $user->isValid()) {
                    $res['user_profile_link'] = '/profile/?user=' . $res['id'];
                }
                $res['user_is_online'] = time() <= $res['lastdate'] + 300;
                $res['search_ip_url'] = '/admin/?act=search_ip&amp;ip=' . long2ip($res['ip']);
                $res['ip'] = long2ip($res['ip']);
                $res['ip_via_proxy'] = ! empty($res['ip_via_proxy']) ? long2ip($res['ip_via_proxy']) : 0;
                $res['search_ip_via_proxy_url'] = '/admin/?act=search_ip&amp;ip=' . $res['ip_via_proxy'];
                yield $res;
            }
        },
    ]
);
