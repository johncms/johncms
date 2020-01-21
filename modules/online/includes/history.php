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

/** @var Johncms\System\Http\Environment $env */
$env = di(Johncms\System\Http\Environment::class);

$data = [];
$data['filters'] = [
    'users'   => [
        'name'   => __('Users'),
        'url'    => '/online/',
        'active' => false,
    ],
    'history' => [
        'name'   => __('History'),
        'url'    => '/online/history/',
        'active' => true,
    ],
];

if ($user->rights) {
    $data['filters']['guest'] = [
        'name'   => __('Guests'),
        'url'    => '/online/guest/',
        'active' => false,
    ];
    $data['filters']['ip'] = [
        'name'   => __('IP Activity'),
        'url'    => '/online/ip/',
        'active' => false,
    ];
}

$total = $db->query('SELECT COUNT(*) FROM `users` WHERE `lastdate` > ' . (time() - 172800 . ' AND `lastdate` < ' . (time() - 310)))->fetchColumn();

// Исправляем запрос на несуществующую страницу
if ($start >= $total) {
    $start = max(0, $total - (($total % $user->config->kmess) == 0 ? $user->config->kmess : ($total % $user->config->kmess)));
}

if ($total) {
    $req = $db->query('SELECT * FROM `users` WHERE `lastdate` > ' . (time() - 172800) . ' AND `lastdate` < ' . (time() - 310) . " ORDER BY `sestime` DESC LIMIT ${start}, " . $user->config->kmess);
    $i = 0;

    while ($res = $req->fetch()) {
        $res['id'] = $res['id'] ?? 0;
        $res['user_profile_link'] = '';
        if (! empty($res['id']) && $user->id !== $res['id'] && $user->isValid()) {
            $res['user_profile_link'] = '/profile/?user=' . $res['id'];
        }
        $res['user_is_online'] = time() <= $res['lastdate'] + 300;
        $res['search_ip_url'] = '/admin/search_ip/?ip=' . long2ip($res['ip']);
        $res['ip'] = long2ip($res['ip']);
        $res['ip_via_proxy'] = ! empty($res['ip_via_proxy']) ? long2ip($res['ip_via_proxy']) : 0;
        $res['search_ip_via_proxy_url'] = '/admin/search_ip/?ip=' . $res['ip_via_proxy'];
        $res['place'] = $tools->displayPlace($res['place']);
        $res['display_date'] = $tools->displayDate($res['sestime']);

        $items[] = $res;
    }
}

$data['pagination'] = $tools->displayPagination('?', $start, $total, $user->config->kmess);
$data['total'] = $total;
$data['items'] = $items ?? [];

echo $view->render(
    'online::users',
    [
        'title'      => $title,
        'page_title' => $title,
        'data'       => $data,
    ]
);
