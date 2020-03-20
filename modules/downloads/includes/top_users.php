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

/**
 * @var PDO $db
 * @var Johncms\System\Legacy\Tools $tools
 */

$title = __('Top Users');
$nav_chain->add($title);
$total = $db->query('SELECT COUNT(DISTINCT user_id) FROM `download__files` WHERE `user_id` > 0 AND `type`<>3')->fetchColumn();

$users = [];
if ($total) {
    $req_down = $db->query(
        "SELECT DISTINCT(d.user_id),
    u.id,
    u.`name`,
    u.rights,
    u.lastdate,
    u.browser,
    u.ip,
    u.ip_via_proxy, (
SELECT COUNT(*) FROM download__files WHERE d.user_id = user_id AND `type` <> 3) AS cnt
FROM download__files d
JOIN users u ON u.id = d.user_id
WHERE d.`type` <> 3 ORDER BY cnt DESC LIMIT ${start}, " . $user->config->kmess
    );
    while ($res_down = $req_down->fetch()) {
        $res_down['files_link'] = '<a href="?act=user_files&amp;id=' .
            $res_down['id'] . '">' . __('User Files') . ': ' . $res_down['cnt'] . '</a>';

        $res_down['user_profile_link'] = '';
        if (! empty($res_down['id']) && $user->isValid() && $user->id !== $res_down['id']) {
            $res_down['user_profile_link'] = '/profile/?user=' . $res_down['id'];
        }

        $res_down['user_rights_name'] = '';
        if (! empty($res_down['rights'])) {
            $res_down['user_rights_name'] = $user_rights_names[$res_down['rights']] ?? '';
        }

        $res_down['user_is_online'] = time() <= $res_down['lastdate'] + 300;
        $res_down['search_ip_url'] = '/admin/search_ip/?ip=' . long2ip((int) $res_down['ip']);
        $res_down['ip'] = long2ip((int) $res_down['ip']);
        $res_down['search_ip_via_proxy_url'] = '/admin/search_ip/?ip=' . long2ip((int) $res_down['ip_via_proxy']);
        $res_down['ip_via_proxy'] = ! empty($res_down['ip_via_proxy']) ? long2ip((int) $res_down['ip_via_proxy']) : 0;

        $users[] = $res_down;
    }
}

echo $view->render(
    'downloads::top_users',
    [
        'title'       => $title,
        'page_title'  => $title,
        'pagination'  => $tools->displayPagination('?act=top_users&amp;', $start, $total, $user->config->kmess),
        'users'       => $users ?? [],
        'total_files' => $total,
        'urls'        => $urls,
    ]
);
