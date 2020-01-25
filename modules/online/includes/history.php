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
        $res['user_id'] = $res['id'];
        $user_properties = new UserProperties();
        $user_data = $user_properties->getFromArray($res);
        $res = array_merge($res, $user_data);
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
