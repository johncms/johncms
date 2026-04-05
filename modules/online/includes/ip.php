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
        'active' => false,
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
        'active' => true,
    ];
}

// Список активных IP, со счетчиком обращений
$ip_array = array_count_values($env->getIpLog());
$total = count($ip_array);
$page = isset($_REQUEST['page']) && (int) $_REQUEST['page'] > 0 ? (int) $_REQUEST['page'] : 1;
$start = isset($_REQUEST['page'])
    ? $page * $user->config->kmess - $user->config->kmess
    : (isset($_GET['start']) ? abs((int) $_GET['start']) : 0);

if ($start >= $total) {
    // Исправляем запрос на несуществующую страницу
    $start = max(0, $total - (($total % $user->config->kmess) == 0 ? $user->config->kmess : ($total % $user->config->kmess)));
}

$end = $start + $user->config->kmess;

if ($end > $total) {
    $end = $total;
}

arsort($ip_array);
$items = [];
if ($total && $user->rights) {
    $currentIp = $env->getIp();
    $ipList = array_slice($ip_array, $start, $end - $start, true);

    foreach ($ipList as $ipLong => $count) {
        $ip = long2ip((int) $ipLong);

        $items[] = [
            'ip'              => $ip,
            'search_ip'       => '/admin/search_ip/?ip=' . $ip,
            'whois_ip'        => '/admin/ip_whois/?ip=' . $ip,
            'current_user_ip' => ((string) $ipLong === (string) $currentIp),
            'count'           => $count,
        ];
    }
}

$data['pagination'] = $total > $user->config->kmess
    ? $tools->displayPagination('?', $start, $total, $user->config->kmess)
    : '';
$data['total'] = $total;
$data['items'] = $items ?? [];

echo $view->render(
    'online::ip',
    [
        'title'      => $title,
        'page_title' => $title,
        'data'       => $data,
    ]
);
