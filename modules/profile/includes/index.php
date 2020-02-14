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

$user_data = (array) $foundUser;

$title = $user_data['id'] !== $user->id ? __('User Profile') : __('My Profile');

$nav_chain->add($title, '?user=' . $user_data['id']);

$user_rights_names = [
    0 => __('User'),
    3 => __('Forum moderator'),
    4 => __('Download moderator'),
    5 => __('Library moderator'),
    6 => __('Super moderator'),
    7 => __('Administrator'),
    9 => __('Supervisor'),
];

// Подготовка дополнительных данных пользователя
$user_data['total_on_site'] = $tools->timecount((int) $user_data['total_on_site']);
$user_data['last_visit'] = time() > $user_data['lastdate'] + 300 ? date('d.m.Y (H:i)', $user_data['lastdate']) : false;
$user_data['user_is_online'] = time() <= $user_data['lastdate'] + 300;
$user_data['birthday'] = ($user_data['dayb'] === date('j') && $user_data['monthb'] === date('n'));
$user_data['birthday_date'] = (empty($user_data['dayb']) ? '' : sprintf('%02d', $user_data['dayb']) . '.' . sprintf('%02d', $user_data['monthb']) . '.' . $user_data['yearofbirth']);
$user_data['place'] = $user_data['id'] !== $user->id ? $tools->displayPlace($user_data['place']) : '';
$user_data['rights_name'] = $user_rights_names[$user_data['rights']] ?? '';
$user_data['ip'] = long2ip((int) $user_data['ip']);
$user_data['search_ip_url'] = '/admin/search_ip/?ip=' . $user_data['ip'];
$user_data['whois_ip_url'] = '/admin/ip_whois/?ip=' . $user_data['ip'];
$user_data['ip_via_proxy'] = ! empty($user_data['ip_via_proxy']) ? long2ip((int) $user_data['ip_via_proxy']) : '';
$user_data['search_ip_via_proxy_url'] = '/admin/search_ip/?ip=' . $user_data['ip_via_proxy'];
$user_data['whois_ip_via_proxy_url'] = '/admin/ip_whois/?ip=' . $user_data['ip_via_proxy'];
$user_data['about'] = $tools->smilies($tools->checkout($user_data['about'], 1, 1));
$user_data['www'] = $tools->checkout($user_data['www'], 0, 1);

$ip_total = $db->query("SELECT COUNT(*) FROM `cms_users_iphistory` WHERE `user_id` = '" . $user_data['id'] . "'")->fetchColumn();
$user_data['ip_history_count'] = $ip_total;
$user_data['ip_history_url'] = '/profile/?act=ip&amp;user=' . $user_data['id'];

if (file_exists(UPLOAD_PATH . 'users/photo/' . $user_data['id'] . '_small.jpg')) {
    $user_data['photo'] = '/upload/users/photo/' . $user_data['id'] . '.jpg';
    $user_data['photo_preview'] = '/upload/users/photo/' . $user_data['id'] . '_small.jpg';
}

if ($config['karma']['on']) {
    $user_data['karma_points'] = $user_data['karma_plus'] - $user_data['karma_minus'];
    if (! empty($user_data['karma_plus']) || ! empty($user_data['karma_minus'])) {
        $user_data['karma_percent'] = round($user_data['karma_points'] / (($user_data['karma_plus'] + $user_data['karma_minus']) / 100));
    } else {
        $user_data['karma_percent'] = 0;
    }

    $user_data['positive_url'] = '?act=karma&amp;user=' . $user_data['id'] . '&amp;type=1';
    $user_data['negative_url'] = '?act=karma&amp;user=' . $user_data['id'];

    if ($user_data['id'] !== $user->id) {
        if (! $user->karma_off && (! $user_data['rights'] || ($user_data['rights'] && ! $config['karma']['adm'])) && $user_data['ip'] !== $user->ip) {
            $sum = $db->query("SELECT SUM(`points`) FROM `karma_users` WHERE `user_id` = '" . $user->id . "' AND `time` >= '" . $user->karma_time . "'")->fetchColumn();
            $count = $db->query("SELECT COUNT(*) FROM `karma_users` WHERE `user_id` = '" . $user->id . "' AND `karma_user` = '" . $user_data['id'] . "' AND `time` > '" . (time() - 86400) . "'")->fetchColumn();
            if (empty($user->ban) && $user->postforum >= $config['karma']['forum'] && ($config['karma']['karma_points'] - $sum) > 0 && ! $count) {
                $user_data['vote_url'] = '?act=karma&amp;mod=vote&amp;user=' . $user_data['id'];
            }
        }
    } else {
        $total_karma = $db->query("SELECT COUNT(*) FROM `karma_users` WHERE `karma_user` = '" . $user->id . "' AND `time` > " . (time() - 86400))->fetchColumn();
        if ($total_karma > 0) {
            $user_data['karma_new_url'] = '?act=karma&amp;mod=new';
            $user_data['karma_new'] = $total_karma;
        }
    }
}

$data = [
    'user'      => $user_data,
    'show_ip'   => $user->rights > 0 && $user->rights >= $user_data['rights'],
    'can_write' => ! $tools->isIgnor($user_data['id']) && is_contact($user_data['id']) !== 2 && ! isset($user->ban['1']) && ! isset($user->ban['3']),
    'blocked'   => is_contact($user_data['id']) === 2,
];

// Различные оповещения
$notifications = [];
if ($user->rights >= 7 && ! $user_data['preg'] && empty($user_data['regadm'])) {
    $notifications[] = __('Pending confirmation');
}
$data['notifications'] = $notifications;

// Счетчики
$total_photo = $db->query("SELECT COUNT(*) FROM `cms_album_files` WHERE `user_id` = '" . $user_data['id'] . "'")->fetchColumn();
$ban = $db->query("SELECT COUNT(*) FROM `cms_ban_users` WHERE `user_id` = '" . $user_data['id'] . "'")->fetchColumn();

$data['counters'] = [
    'photo' => $total_photo,
    'ban'   => $ban,
];

// Админские кнопки
$buttons = [];

if (is_contact($user_data['id']) !== 2) {
    if (! is_contact($foundUser->id)) {
        $buttons[] = [
            'url'  => '../mail/?id=' . $user_data['id'],
            'name' => __('Add to Contacts'),
        ];
    } else {
        $buttons[] = [
            'url'  => '../mail/?act=deluser&amp;id=' . $user_data['id'],
            'name' => __('Remove from Contacts'),
        ];
    }
}

if ($user_data['id'] === $user->id || $user->rights === 9 || ($user->rights === 7 && $user->rights > $user_data['rights'])) {
    $buttons[] = [
        'url'  => '?act=edit&amp;user=' . $user_data['id'],
        'name' => __('Edit'),
    ];
}
if ($user_data['id'] !== $user->id && $user->rights >= 7 && $user->rights > $user_data['rights']) {
    $buttons[] = [
        'url'  => '/admin/usr_del/?id=' . $user_data['id'],
        'name' => __('Delete'),
    ];
}
if ($user_data['id'] !== $user->id && $user->rights > $user_data['rights']) {
    $buttons[] = [
        'url'  => '?act=ban&amp;mod=do&amp;user=' . $user_data['id'],
        'name' => __('Ban'),
    ];
}
$data['buttons'] = $buttons;

// Выводим шаблон
echo $view->render(
    'profile::index',
    [
        'title'      => $title,
        'page_title' => $title,
        'data'       => $data,
    ]
);
