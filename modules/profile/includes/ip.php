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

$title = __('IP History');

// Проверяем права доступа
if (! $user->rights && $user->id !== $user_data->id) {
    echo $view->render(
        'system::pages/result',
        [
            'title'   => $title,
            'type'    => 'alert-danger',
            'message' => __('Access forbidden'),
        ]
    );
    exit;
}

$total = $db->query("SELECT COUNT(*) FROM `cms_users_iphistory` WHERE `user_id` = '" . $user_data->id . "'")->fetchColumn();
if ($total) {
    $req = $db->query("SELECT * FROM `cms_users_iphistory` WHERE `user_id` = '" . $user_data->id . "' ORDER BY `time` DESC LIMIT ${start}, " . $user->set_user->kmess);
    $items = [];
    while ($res = $req->fetch()) {
        $res['ip'] = long2ip((int) $res['ip']);
        $res['search_url'] = '/admin/search_ip/?mod=history&amp;ip=' . $res['ip'];
        $res['display_date'] = $tools->displayDate($res['time']);
        $items[] = $res;
    }
}

$data['back_url'] = '?user=' . $user_data->id;
$data['total'] = $total;
$data['filters'] = [];
$data['pagination'] = $tools->displayPagination('?act=ip&amp;user=' . $user_data->id . '&amp;', $start, $total, $user->set_user->kmess);
$data['items'] = $items ?? [];

echo $view->render(
    'profile::ip_history',
    [
        'title'      => $title,
        'page_title' => $title,
        'data'       => $data,
    ]
);
