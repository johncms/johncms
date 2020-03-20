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

$url = '/community/top/';

$tabs = [
    'forum' => [
        'name'   => __('Forum'),
        'url'    => $url,
        'active' => false,
    ],
    'guest' => [
        'name'   => __('Guestbook'),
        'url'    => $url . 'guest/',
        'active' => false,
    ],
    'comm'  => [
        'name'   => __('Comments'),
        'url'    => $url . 'comm/',
        'active' => false,
    ],
];

if ($config['karma']) {
    $tabs['karma'] = [
        'name'   => __('Karma'),
        'url'    => $url . 'karma/',
        'active' => false,
    ];
}


switch ($mod) {
    case 'guest':
        // Топ Гостевой
        $users = (new User())->where('postguest', '>', 0)->orderBy('postguest', 'desc')->limit(9)->get();
        $title = __('Most active in Guestbook');
        $active = 'guest';
        break;

    case 'comm':
        // Топ комментариев
        $users = (new User())->where('komm', '>', 0)->orderBy('komm', 'desc')->limit(9)->get();
        $title = __('Most commentators');
        $active = 'comm';
        break;

    case 'karma':
        // Топ Кармы
        if ($config['karma']) {
            $users = (new User())->selectRaw('*, (`karma_plus` - `karma_minus`) as `karma`')->whereRaw('(`karma_plus` - `karma_minus`) > 0')->orderBy('karma', 'desc')->limit(9)->get();
            $title = __('Best Karma');
            $active = 'karma';
        }
        break;

    default:
        // Топ Форума
        $users = (new User())->where('postforum', '>', 0)->orderBy('postforum', 'desc')->limit(9)->get();
        $title = __('Most active in Forum');
        $active = 'forum';
}

$tabs[$active]['active'] = true;

$nav_chain->add($title);

$data = [
    'total'      => $users->count(),
    'active_tab' => $active,
    'tabs'       => $tabs,
    'list'       => $users,
];

echo $view->render(
    'users::top',
    [
        'title'      => $title,
        'page_title' => $title,
        'data'       => $data,
    ]
);
