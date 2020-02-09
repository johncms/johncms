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

$mod = $request->getQuery('mod', '', FILTER_SANITIZE_STRING);

$title = __('List of users');
$nav_chain->add($title);
$album_access = 'AND (`access` > 1 OR `t1`.`user_id` = ' . $user->id . ')';
$count_access = 'WHERE (`access` > 1 OR `user_id` = ' . $user->id . ')';
if ($user->rights >= 6) {
    $album_access = '';
    $count_access = '';
}
// Список посетителей. у которых есть фотографии
switch ($mod) {
    case 'boys':
        $sql = 'WHERE `u`.`sex` = "m" ' . $album_access;
        break;

    case 'girls':
        $sql = 'WHERE `u`.`sex` = "zh" ' . $album_access;
        break;
    default:
        $sql = 'WHERE `u`.`sex` <> "" ' . $album_access;
}

$data = [];
$data['filters'] = [
    'all'   => [
        'name'   => __('All'),
        'url'    => './users',
        'active' => ! $mod,
    ],
    'boys'  => [
        'name'   => __('Guys'),
        'url'    => './users?mod=boys',
        'active' => $mod === 'boys',
    ],
    'girls' => [
        'name'   => __('Girls'),
        'url'    => './users?mod=girls',
        'active' => $mod === 'girls',
    ],
];

$total = $db->query('SELECT COUNT(DISTINCT(`t1`.`user_id`))
FROM `cms_album_cat` t1
JOIN `users` u ON `u`.`id` = `t1`.`user_id` ' . $sql . ';')->fetchColumn();

if ($total) {
// TODO: create index idx_combine ON cms_album_cat/cms_albun_files (access, user_id);
    $req = $db->query('SELECT DISTINCT (`t1`.`user_id`) AS id,
    `cat2`.`count_albums`,
    `a2`.`count`,
    `u`.`name` AS nick,
    `u`.`lastdate`
FROM `cms_album_cat` t1
JOIN (SELECT `user_id`, COUNT(*) `count_albums` FROM `cms_album_cat` ' . $count_access . ' GROUP BY `user_id`) cat2
ON `t1`.`user_id` = `cat2`.`user_id`
LEFT JOIN (SELECT `user_id`, COUNT(*) `count` FROM `cms_album_files` ' . $count_access . ' GROUP BY `user_id`) a2
ON `a2`.`user_id` = `cat2`.`user_id`
JOIN `users` u ON `u`.`id` = `t1`.`user_id` ' . $sql . '
ORDER BY `u`.`name` ASC LIMIT ' . $start . ', ' . $user->config->kmess);
    $users = [];
    while ($res = $req->fetch()) {
        $res['user_is_online'] = time() <= $res['lastdate'] + 300;
        $res['album_url'] = './list?user=' . $res['id'];
        if (! $res['count']) {
            $res['count'] = 0;
        }
        $users[] = $res;
    }
}

$data['total'] = $total;
$data['pagination'] = $tools->displayPagination('?' . ($mod ? 'mod=' . $mod . '&amp;' : ''), $start, $total, $user->config->kmess);
$data['users'] = $users ?? [];

echo $view->render(
    'album::users',
    [
        'title'      => $title,
        'page_title' => $title,
        'data'       => $data,
    ]
);
