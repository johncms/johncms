<?php

/**
 * This file is part of JohnCMS Content Management System.
 *
 * @copyright JohnCMS Community
 * @license   https://opensource.org/licenses/GPL-3.0 GPL-3.0
 * @link      https://johncms.com JohnCMS Project
 */

declare(strict_types=1);

use Illuminate\Pagination\LengthAwarePaginator;
use Johncms\Users\User;

defined('_IN_JOHNCMS') || die('Error: restricted access');

/** @var Johncms\System\Http\Environment $env */
$env = di(Johncms\System\Http\Environment::class);

// Показываем список Online
$data = [];
$data['filters'] = [
    'users'   => [
        'name'   => __('Users'),
        'url'    => '/online/',
        'active' => true,
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
        'active' => false,
    ];
}
$title = __('Who is online?');

/** @var LengthAwarePaginator $users */
$users = (new User())
    ->where('lastdate', '>', (time() - 300))
    ->orderBy('lastdate', 'desc')
    ->paginate($user->config->kmess);

$total = $users->total();

if ($total) {
    $items = $users->getItems()->map(
        static function ($user) use ($tools) {
            /** @var $user User */
            $user->place_name = $tools->displayPlace($user->place);
            $user->display_date = $user->movings . ' - ' . $tools->timecount(time() - $user->sestime);
            return $user;
        }
    );
}

$data['pagination'] = $users->render();
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
