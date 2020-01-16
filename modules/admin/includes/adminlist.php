<?php

/**
 * This file is part of JohnCMS Content Management System.
 *
 * @copyright JohnCMS Community
 * @license   https://opensource.org/licenses/GPL-3.0 GPL-3.0
 * @link      https://johncms.com JohnCMS Project
 */

declare(strict_types=1);

defined('_IN_JOHNADM') || die('Error: restricted access');

/**
 * @var PDO $db
 * @var Johncms\System\Legacy\Tools $tools
 */

$title = __('Administration');
$nav_chain->add($title);
$data = [];

$sw = 0;
$adm = 0;
$smd = 0;
$mod = 0;

$items = [];

$req = $db->query("SELECT * FROM `users` WHERE `rights` = '9'");
if ($req->rowCount()) {
    $block = [
        'name'  => __('Supervisors'),
        'items' => [],
    ];

    while ($res = $req->fetch()) {
        $res['user_profile_link'] = '';
        if (! empty($res['id']) && $user->id !== $res['id'] && $user->isValid()) {
            $res['user_profile_link'] = '/profile/?user=' . $res['id'];
        }
        $res['user_is_online'] = time() <= $res['lastdate'] + 300;
        $res['search_ip_url'] = '/admin/search_ip/?ip=' . long2ip($res['ip']);
        $res['ip'] = long2ip($res['ip']);
        $res['ip_via_proxy'] = ! empty($res['ip_via_proxy']) ? long2ip($res['ip_via_proxy']) : 0;
        $res['search_ip_via_proxy_url'] = '/admin/search_ip/?ip=' . $res['ip_via_proxy'];
        $block['items'][] = $res;
        $sw++;
    }

    $items[] = $block;
}

$req = $db->query("SELECT * FROM `users` WHERE `rights` = '7' ORDER BY `name` ASC");

if ($req->rowCount()) {
    $block = [
        'name'  => __('Administrators'),
        'items' => [],
    ];

    while ($res = $req->fetch()) {
        $res['user_profile_link'] = '';
        if (! empty($res['id']) && $user->id !== $res['id'] && $user->isValid()) {
            $res['user_profile_link'] = '/profile/?user=' . $res['id'];
        }
        $res['user_is_online'] = time() <= $res['lastdate'] + 300;
        $res['search_ip_url'] = '/admin/search_ip/?ip=' . long2ip($res['ip']);
        $res['ip'] = long2ip($res['ip']);
        $res['ip_via_proxy'] = ! empty($res['ip_via_proxy']) ? long2ip($res['ip_via_proxy']) : 0;
        $res['search_ip_via_proxy_url'] = '/admin/search_ip/?ip=' . $res['ip_via_proxy'];
        $block['items'][] = $res;
        $adm++;
    }

    $items[] = $block;
}

$req = $db->query("SELECT * FROM `users` WHERE `rights` = '6' ORDER BY `name` ASC");

if ($req->rowCount()) {
    $block = [
        'name'  => __('Super Moderators'),
        'items' => [],
    ];

    while ($res = $req->fetch()) {
        $res['user_profile_link'] = '';
        if (! empty($res['id']) && $user->id !== $res['id'] && $user->isValid()) {
            $res['user_profile_link'] = '/profile/?user=' . $res['id'];
        }
        $res['user_is_online'] = time() <= $res['lastdate'] + 300;
        $res['search_ip_url'] = '/admin/search_ip/?ip=' . long2ip($res['ip']);
        $res['ip'] = long2ip($res['ip']);
        $res['ip_via_proxy'] = ! empty($res['ip_via_proxy']) ? long2ip($res['ip_via_proxy']) : 0;
        $res['search_ip_via_proxy_url'] = '/admin/search_ip/?ip=' . $res['ip_via_proxy'];
        $block['items'][] = $res;
        ++$smd;
    }

    $items[] = $block;
}

$req = $db->query("SELECT * FROM `users` WHERE `rights` BETWEEN '1' AND '5' ORDER BY `name` ASC");

if ($req->rowCount()) {
    $block = [
        'name'  => __('Moderators'),
        'items' => [],
    ];

    while ($res = $req->fetch()) {
        $res['user_profile_link'] = '';
        if (! empty($res['id']) && $user->id !== $res['id'] && $user->isValid()) {
            $res['user_profile_link'] = '/profile/?user=' . $res['id'];
        }
        $res['user_is_online'] = time() <= $res['lastdate'] + 300;
        $res['search_ip_url'] = '/admin/search_ip/?ip=' . long2ip($res['ip']);
        $res['ip'] = long2ip($res['ip']);
        $res['ip_via_proxy'] = ! empty($res['ip_via_proxy']) ? long2ip($res['ip_via_proxy']) : 0;
        $res['search_ip_via_proxy_url'] = '/admin/search_ip/?ip=' . $res['ip_via_proxy'];
        $block['items'][] = $res;
        ++$mod;
    }

    $items[] = $block;
}

$data['total'] = ($sw + $adm + $smd + $mod);
$data['items'] = $items ?? [];
$data['back_url'] = '/admin/';

echo $view->render(
    'admin::admin_list',
    [
        'title'      => $title,
        'page_title' => $title,
        'data'       => $data,
    ]
);
