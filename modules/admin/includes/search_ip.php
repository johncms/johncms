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

defined('_IN_JOHNADM') || die('Error: restricted access');

/**
 * @var Johncms\System\Legacy\Tools $tools
 */

$data = [];
$error = [];

if (! $search = $request->getQuery('ip', null, FILTER_VALIDATE_IP)) {
    $search = $request->getPost('search') ?? rawurldecode(trim($request->getQuery('search', ''))) ?? '';
}

$title = __('Search IP');
$nav_chain->add($title);

$data['filters'] = [
    [
        'url'    => '?search=' . rawurlencode($search),
        'name'   => __('Actual IP'),
        'active' => ! $mod,
    ],
    [
        'url'    => '?mod=history&amp;search=' . rawurlencode($search),
        'name'   => __('IP history'),
        'active' => $mod === 'history',
    ],
];

$data['search_query'] = $tools->checkout($search);

if ($search) {
    if (strpos($search, '-') !== false) {
        // Обрабатываем диапазон адресов
        $array = explode('-', $search);
        $ip = trim($array[0]);

        if (! preg_match('#^(?:(?:\d{1,2}|1\d\d|2[0-4]\d|25[0-5])\.){3}(?:\d{1,2}|1\d\d|2[0-4]\d|25[0-5])$#', $ip)) {
            $error[] = __('First IP is entered incorrectly');
        } else {
            $ip1 = ip2long($ip);
        }

        $ip = trim($array[1]);

        if (! preg_match('#^(?:(?:\d{1,2}|1\d\d|2[0-4]\d|25[0-5])\.){3}(?:\d{1,2}|1\d\d|2[0-4]\d|25[0-5])$#', $ip)) {
            $error[] = __('Second IP is entered incorrectly');
        } else {
            $ip2 = ip2long($ip);
        }
    } elseif (strpos($search, '*') !== false) {
        // Обрабатываем адреса с маской
        $array = explode('.', $search);
        $ipt1 = [];
        $ipt2 = [];
        for ($i = 0; $i < 4; $i++) {
            if (! isset($array[$i]) || $array[$i] === '*') {
                $ipt1[$i] = '0';
                $ipt2[$i] = '255';
            } elseif (is_numeric($array[$i]) && $array[$i] >= 0 && $array[$i] <= 255) {
                $ipt1[$i] = $array[$i];
                $ipt2[$i] = $array[$i];
            } else {
                $error = __('Invalid IP');
            }
        }

        $ip1 = ip2long($ipt1[0] . '.' . $ipt1[1] . '.' . $ipt1[2] . '.' . $ipt1[3]);
        $ip2 = ip2long($ipt2[0] . '.' . $ipt2[1] . '.' . $ipt2[2] . '.' . $ipt2[3]);
    } elseif (! preg_match('#^(?:(?:\d{1,2}|1\d\d|2[0-4]\d|25[0-5])\.){3}(?:\d{1,2}|1\d\d|2[0-4]\d|25[0-5])$#', $search)) {
        $error = __('Invalid IP');
    } else {
        $ip1 = ip2long($search);
        $ip2 = $ip1;
    }
}

if ($search && ! $error) {
    /** @var PDO $db */
    $db = di(PDO::class);

    // Выводим результаты поиска
    if ($mod === 'history') {
        $total = $db->query("SELECT COUNT(DISTINCT `cms_users_iphistory`.`user_id`) FROM `cms_users_iphistory` WHERE `ip` BETWEEN ${ip1} AND ${ip2} OR `ip_via_proxy` BETWEEN ${ip1} AND ${ip2}")->fetchColumn();
    } else {
        $total = $db->query("SELECT COUNT(*) FROM `users` WHERE `ip` BETWEEN ${ip1} AND ${ip2} OR `ip_via_proxy` BETWEEN ${ip1} AND ${ip2}")->fetchColumn();
    }


    if ($total) {
        if ($mod === 'history') {
            $req = $db->query("SELECT
    hst.ip,
    hst.ip_via_proxy,
    hst.`time`,
    `u`.`id`,
    `u`.`name`,
    `u`.`rights`,
    `u`.`lastdate`,
    `u`.`sex`,
    `u`.`status`,
    `u`.`datereg`,
    `u`.`browser`
FROM `cms_users_iphistory` hst
JOIN `users` u ON u.id = hst.user_id
JOIN (SELECT user_id, MAX(`time`) `mtime` FROM `cms_users_iphistory` GROUP BY user_id) t
ON t.mtime = hst.`time` AND t.user_id = u.id
WHERE hst.`ip` BETWEEN ${ip1} AND ${ip2} OR hst.`ip_via_proxy` BETWEEN ${ip1} AND ${ip2}
ORDER BY hst.`time` DESC, u.`name` ASC LIMIT " . $start . ',' . $user->config->kmess);
        } else {
            $req = $db->query(
                "SELECT * FROM `users`
            WHERE `ip` BETWEEN ${ip1} AND ${ip2} OR `ip_via_proxy` BETWEEN ${ip1} AND ${ip2}
            ORDER BY `ip` ASC, `name` ASC LIMIT " . $start . ',' . $user->config->kmess
            );
        }

        $items = [];
        while ($res = $req->fetch()) {
            $res['user_id'] = $res['id'];
            $user_properties = new UserProperties();
            $user_data = $user_properties->getFromArray($res);
            $res = array_merge($res, $user_data);
            $items[] = $res;
        }
    }


    if ($total > $user->config->kmess) {
        $data['pagination'] = $tools->displayPagination('?' . ($mod === 'history' ? 'mod=history&amp;' : '') . 'search=' . urlencode($search) . '&amp;', $start, $total, $user->config->kmess);
    }
}

$data['back_url'] = '/admin/';

$data['errors'] = $error ?? [];
$data['total'] = $total ?? 0;

$data['items'] = $items ?? [];

echo $view->render(
    'admin::search_ip',
    [
        'title'      => $title,
        'page_title' => $title,
        'data'       => $data,
    ]
);
