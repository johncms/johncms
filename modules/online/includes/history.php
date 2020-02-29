<?php

/**
 * This file is part of JohnCMS Content Management System.
 *
 * @copyright JohnCMS Community
 * @license   https://opensource.org/licenses/GPL-3.0 GPL-3.0
 * @link      https://johncms.com JohnCMS Project
 */

declare(strict_types=1);

use Johncms\Users\User;

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
$users = (new User())->whereBetween('lastdate', [(time() - 172800), (time() - 310)]);
$total = $users->count();

// Исправляем запрос на несуществующую страницу
if ($start >= $total) {
    $start = max(0, $total - (($total % $user->config->kmess) === 0 ? $user->config->kmess : ($total % $user->config->kmess)));
}

if ($total) {
    $req = $users->offset($start)
        ->limit($user->config->kmess)
        ->get()
        ->map(
            static function ($user) use ($tools) {
                /** @var $user User */
                $user->place_name = $tools->displayPlace($user->place);
                $user->display_date = $tools->displayDate($user->sestime);
                return $user;
            }
        );
}

$data['pagination'] = $tools->displayPagination('?', $start, $total, $user->config->kmess);
$data['total'] = $total;
$data['items'] = $req ?? [];

echo $view->render(
    'online::users',
    [
        'title'      => $title,
        'page_title' => $title,
        'data'       => $data,
    ]
);
