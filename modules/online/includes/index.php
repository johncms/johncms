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

// Показываем список Online
$data = [];
$data['filters'] = [
    'users'   => [
        'name'   => _t('Users'),
        'url'    => '/online/',
        'active' => true,
    ],
    'history' => [
        'name'   => _t('History'),
        'url'    => '/online/history/',
        'active' => false,
    ],
];

if ($user->rights) {
    $data['filters']['guest'] = [
        'name'   => _t('Guests'),
        'url'    => '/online/guest/',
        'active' => false,
    ];
    $data['filters']['ip'] = [
        'name'   => _t('IP Activity'),
        'url'    => '/online/ip/',
        'active' => false,
    ];
}
$title = _t('Who is online?');

$total = $db->query('SELECT COUNT(*) FROM `users` WHERE `lastdate` > ' . (time() - 300))->fetchColumn();

// Исправляем запрос на несуществующую страницу
if ($start >= $total) {
    $start = max(0, $total - (($total % $user->config->kmess) == 0 ? $user->config->kmess : ($total % $user->config->kmess)));
}

if ($total) {
    $req = $db->query('SELECT * FROM `users` WHERE `lastdate` > ' . (time() - 300) . " ORDER BY `name` ASC LIMIT ${start}, " . $user->config->kmess);
    $items = [];
    while ($res = $req->fetch()) {
        $res['id'] = $res['id'] ?? 0;
        $res['user_profile_link'] = '';
        if (! empty($res['id']) && $user->id !== $res['id'] && $user->isValid()) {
            $res['user_profile_link'] = '/profile/?user=' . $res['id'];
        }
        $res['user_is_online'] = time() <= $res['lastdate'] + 300;
        $res['search_ip_url'] = '/admin/?act=search_ip&amp;ip=' . long2ip($res['ip']);
        $res['ip'] = long2ip($res['ip']);
        $res['ip_via_proxy'] = ! empty($res['ip_via_proxy']) ? long2ip($res['ip_via_proxy']) : 0;
        $res['search_ip_via_proxy_url'] = '/admin/?act=search_ip&amp;ip=' . $res['ip_via_proxy'];
        $res['place'] = $tools->displayPlace($res['place']);
        $res['display_date'] = $res['movings'] . ' - ' . $tools->timecount(time() - $res['sestime']);
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
