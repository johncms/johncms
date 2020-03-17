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

/**
 * @var User $user_data
 * @var User $user
 */

$title = $user_data->id !== $user->id ? __('User Profile') : __('My Profile');

$nav_chain->add($title, '?user=' . $user_data->id);

// Вычисляемые поля, которые нужно добавить в массив
$appends = [
    'is_online',
    'rights_name',
    'profile_url',
    'search_ip_url',
    'whois_ip_url',
    'search_ip_via_proxy_url',
    'whois_ip_via_proxy_url',
    'is_birthday',
    'birthday_date',
    'display_place',
    'formatted_about',
    'website',
    'photo',
    'last_visit',
];

// Готовим массив пользовательских данных для передачи в шаблон
$user_data_array = $user_data->setAppends($appends)->toArray();

// История IP
$user_data_array['ip_history_count'] = $user_data->ipHistory()->count();
$user_data_array['ip_history_url'] = '/profile/?act=ip&amp;user=' . $user_data->id;

// Карма
if ($config['karma']['on']) {
    $user_data_array['karma_points'] = $user_data->karma_plus - $user_data->karma_minus;
    if (! empty($user_data->karma_plus) || ! empty($user_data->karma_minus)) {
        $user_data_array['karma_percent'] = round($user_data_array['karma_points'] / (($user_data->karma_plus + $user_data->karma_minus) / 100));
    } else {
        $user_data_array['karma_percent'] = 0;
    }

    $user_data_array['positive_url'] = '?act=karma&amp;user=' . $user_data->id . '&amp;type=1';
    $user_data_array['negative_url'] = '?act=karma&amp;user=' . $user_data->id;

    if ($user_data->id !== $user->id) {
        if (! $user->karma_off && (! $user_data->rights || ($user_data->rights && ! $config['karma']['adm'])) && $user_data->ip !== $user->ip) {
            $sum = $db->query("SELECT SUM(`points`) FROM `karma_users` WHERE `user_id` = '" . $user->id . "' AND `time` >= '" . $user->karma_time . "'")->fetchColumn();
            $count = $db->query("SELECT COUNT(*) FROM `karma_users` WHERE `user_id` = '" . $user->id . "' AND `karma_user` = '" . $user_data->id . "' AND `time` > '" . (time() - 86400) . "'")->fetchColumn();
            if (empty($user->ban) && $user->postforum >= $config['karma']['forum'] && ($config['karma']['karma_points'] - $sum) > 0 && ! $count) {
                $user_data_array['vote_url'] = '?act=karma&amp;mod=vote&amp;user=' . $user_data->id;
            }
        }
    } else {
        $total_karma = $db->query("SELECT COUNT(*) FROM `karma_users` WHERE `karma_user` = '" . $user->id . "' AND `time` > " . (time() - 86400))->fetchColumn();
        if ($total_karma > 0) {
            $user_data_array['karma_new_url'] = '?act=karma&amp;mod=new';
            $user_data_array['karma_new'] = $total_karma;
        }
    }
}

// Готовим массив для шаблона
$data = [
    'user'      => $user_data_array,
    'show_ip'   => $user->rights > 0 && $user->rights >= $user_data->rights,
    'can_write' => ! $tools->isIgnor($user_data->id) && is_contact($user_data->id) !== 2 && ! isset($user->ban['1']) && ! isset($user->ban['3']),
    'blocked'   => is_contact($user_data->id) === 2,
];

// Различные оповещения
$notifications = [];
if ($user->rights >= 7 && ! $user_data->preg && empty($user_data->regadm)) {
    $notifications[] = __('Pending confirmation');
}
$data['notifications'] = $notifications;

// Счетчики
$ban_user = $db->query("SELECT `ban_reason`, `ban_time` AS `mtime` FROM `cms_ban_users` WHERE `user_id` = '" . $user_data->id . "' ORDER BY mtime DESC LIMIT 1")->fetch();

$data['active_ban'] = false;
$data['active_ban_reason'] = '';
if (! empty($ban_user)) {
    $data['active_ban_reason'] = $ban_user['ban_reason'];
    $data['active_ban'] = $ban_user['mtime'] > time();
}

$data['counters'] = [
    'ban'   => $user_data->bans()->count(),
];

// Админские кнопки
$buttons = [];

if (is_contact($user_data->id) !== 2) {
    if (! is_contact($user_data->id)) {
        $buttons[] = [
            'url'  => '../mail/?id=' . $user_data->id,
            'name' => __('Add to Contacts'),
        ];
    } else {
        $buttons[] = [
            'url'  => '../mail/?act=deluser&amp;id=' . $user_data->id,
            'name' => __('Remove from Contacts'),
        ];
    }
}

if ($user_data->id === $user->id || $user->rights === 9 || ($user->rights === 7 && $user->rights > $user_data->rights)) {
    $buttons[] = [
        'url'  => '?act=edit&amp;user=' . $user_data->id,
        'name' => __('Edit'),
    ];
}
if ($user_data->id !== $user->id && $user->rights >= 7 && $user->rights > $user_data->rights) {
    $buttons[] = [
        'url'  => '/admin/usr_del/?id=' . $user_data->id,
        'name' => __('Delete'),
    ];
}
if ($user_data->id !== $user->id && $user->rights > $user_data->rights) {
    $buttons[] = [
        'url'  => '?act=ban&amp;mod=do&amp;user=' . $user_data->id,
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
