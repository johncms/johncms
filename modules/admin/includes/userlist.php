<?php

/**
 * This file is part of JohnCMS Content Management System.
 *
 * @copyright JohnCMS Community
 * @license   https://opensource.org/licenses/GPL-3.0 GPL-3.0
 * @link      https://johncms.com JohnCMS Project
 */

declare(strict_types=1);

use Johncms\NavChain;
use Johncms\UserProperties;

defined('_IN_JOHNADM') || die('Error: restricted access');

/**
 * @var PDO $db
 * @var Johncms\System\Legacy\Tools $tools
 */

/** @var NavChain $navChain */
$navChain = di(NavChain::class);
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
                $res['user_id'] = $res['id'];
                $user_properties = new UserProperties();
                $user_data = $user_properties->getFromArray($res);
                $res = array_merge($res, $user_data);
                yield $res;
            }
        },
    ]
);
