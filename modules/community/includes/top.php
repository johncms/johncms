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
        $req = $db->query('SELECT * FROM `users` WHERE `postguest` > 0 ORDER BY `postguest` DESC LIMIT 9');
        $title = __('Most active in Guestbook');
        $active = 'guest';
        break;

    case 'comm':
        // Топ комментариев
        $req = $db->query('SELECT * FROM `users` WHERE `komm` > 0 ORDER BY `komm` DESC LIMIT 9');
        $title = __('Most commentators');
        $active = 'comm';
        break;

    case 'karma':
        // Топ Кармы
        if ($config['karma']) {
            $req = $db->query('SELECT *, (`karma_plus` - `karma_minus`) AS `karma` FROM `users` WHERE (`karma_plus` - `karma_minus`) > 0 ORDER BY `karma` DESC LIMIT 9');
            $title = __('Best Karma');
            $active = 'karma';
        }
        break;

    default:
        // Топ Форума
        $req = $db->query('SELECT * FROM `users` WHERE `postforum` > 0 ORDER BY `postforum` DESC LIMIT 9');
        $title = __('Most active in Forum');
        $active = 'forum';
}

$tabs[$active]['active'] = true;

$nav_chain->add($title);

$data = [
    'total'      => $req->rowCount(),
    'active_tab' => $active,
    'tabs'       => $tabs,
    'list'       => static function () use ($req, $user) {
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
];

echo $view->render(
    'users::top',
    [
        'title'      => $title,
        'page_title' => $title,
        'data'       => $data,
    ]
);
