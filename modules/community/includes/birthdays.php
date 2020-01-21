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

$total = $db->query("SELECT COUNT(*) FROM `users` WHERE `dayb` = '" . date('j', time()) . "' AND `monthb` = '" . date('n', time()) . "' AND `preg` = '1'")->fetchColumn();
$req = $db->query("SELECT * FROM `users` WHERE `dayb` = '" . date('j', time()) . "' AND `monthb` = '" . date('n', time()) . "' AND `preg` = '1' LIMIT ${start}, " . $user->config->kmess);

$nav_chain->add(__('Birthdays'));

echo $view->render(
    'users::users',
    [
        'pagination' => $tools->displayPagination('?', $start, $total, $user->config->kmess),
        'title'      => __('Birthdays'),
        'page_title' => __('Birthdays'),
        'total'      => $total,
        'list'       =>
            static function () use ($req, $user) {
                while ($res = $req->fetch()) {
                    $res['user_profile_link'] = '';
                    if (! empty($res['id']) && $user->id !== $res['id'] && $user->isValid()) {
                        $res['user_profile_link'] = '/profile/?user=' . $res['id'];
                    }
                    $res['user_is_online'] = time() <= $res['lastdate'] + 300;
                    $res['search_ip_url'] = '/admin/search_ip/?ip=' . long2ip($res['ip']);
                    $res['ip'] = long2ip($res['ip']);
                    $res['ip_via_proxy'] = ! empty($res['ip_via_proxy']) ? long2ip($res['ip_via_proxy']) : 0;
                    $res['search_ip_via_proxy_url'] = '/admin/search_ip/?ip=' . $res['ip_via_proxy'];
                    yield $res;
                }
            },
    ]
);
