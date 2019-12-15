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
 * @var Johncms\System\Utility\Tools $tools
 */

$title = _t('Top Users');
$nav_chain->add($title);
$req = $db->query('SELECT * FROM `download__files` WHERE `user_id` > 0 GROUP BY `user_id` ORDER BY COUNT(`user_id`)');
$total = $req->rowCount();

$users = [];
if ($total) {
    $req_down = $db->query("SELECT *, COUNT(`user_id`) AS `count` FROM `download__files` WHERE `user_id` > 0 GROUP BY `user_id` ORDER BY `count` DESC LIMIT ${start}, " . $user->config->kmess);
    while ($res_down = $req_down->fetch()) {
        $foundUser = $db->query('SELECT * FROM `users` WHERE `id`=' . $res_down['user_id'])->fetch();
        $foundUser['files_link'] = '<a href="?act=user_files&amp;id=' . $foundUser['id'] . '">' . _t('User Files') . ': ' . $res_down['count'] . '</a>';

        $foundUser['user_avatar'] = '';
        if (! empty($foundUser['id'])) {
            $avatar = 'users/avatar/' . $foundUser['id'] . '.png';
            if (file_exists(UPLOAD_PATH . $avatar)) {
                $foundUser['user_avatar'] = UPLOAD_PUBLIC_PATH . $avatar;
            }
        }

        $foundUser['user_profile_link'] = '';
        if (! empty($foundUser['id']) && $user->isValid() && $user->id !== $foundUser['id']) {
            $foundUser['user_profile_link'] = '/profile/?user=' . $foundUser['id'];
        }

        $foundUser['user_rights_name'] = '';
        if (! empty($foundUser['rights'])) {
            $foundUser['user_rights_name'] = $user_rights_names[$foundUser['rights']] ?? '';
        }

        $foundUser['user_is_online'] = time() <= $foundUser['lastdate'] + 300;
        $foundUser['search_ip_url'] = '/admin/?act=search_ip&amp;ip=' . long2ip($foundUser['ip']);
        $foundUser['ip'] = long2ip($foundUser['ip']);
        $foundUser['search_ip_via_proxy_url'] = '/admin/?act=search_ip&amp;ip=' . long2ip($foundUser['ip_via_proxy']);
        $foundUser['ip_via_proxy'] = ! empty($foundUser['ip_via_proxy']) ? long2ip($foundUser['ip_via_proxy']) : 0;

        $users[] = $foundUser;
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
