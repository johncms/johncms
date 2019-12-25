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

$mod = $request->getQuery('mod', '', FILTER_SANITIZE_STRING);

$title = _t('List of users');
$nav_chain->add($title);

// Список посетителей. у которых есть фотографии
switch ($mod) {
    case 'boys':
        $sql = "WHERE `users`.`sex` = 'm'";
        break;

    case 'girls':
        $sql = "WHERE `users`.`sex` = 'zh'";
        break;
    default:
        $sql = "WHERE `users`.`sex` != ''";
}

$data = [];
$data['filters'] = [
    'all'   => [
        'name'   => _t('All'),
        'url'    => '?act=users',
        'active' => ! $mod,
    ],
    'boys'  => [
        'name'   => _t('Guys'),
        'url'    => '?act=users&amp;mod=boys',
        'active' => $mod === 'boys',
    ],
    'girls' => [
        'name'   => _t('Girls'),
        'url'    => '?act=users&amp;mod=girls',
        'active' => $mod === 'girls',
    ],
];

$total = $db->query(
    "SELECT COUNT(DISTINCT `user_id`)
    FROM `cms_album_files`
    LEFT JOIN `users` ON `cms_album_files`.`user_id` = `users`.`id` ${sql}
"
)->fetchColumn();

if ($total) {
    $album_access = ($foundUser['id'] === $user->id || $user->rights >= 6 ? '' : ' AND albums.access > 1');
    $req = $db->query(
        "SELECT `cms_album_files`.*, COUNT(`cms_album_files`.`id`) AS `count`, `users`.`id` AS `uid`, users.lastdate, `users`.`name` AS `nick`,
        (SELECT COUNT(*) FROM cms_album_cat as albums WHERE albums.user_id = users.id ${album_access}) AS count_albums
        FROM `cms_album_files`
        LEFT JOIN `users` ON `cms_album_files`.`user_id` = `users`.`id` ${sql}
        GROUP BY `cms_album_files`.`user_id` ORDER BY `users`.`name` ASC LIMIT ${start}, " . $user->config->kmess
    );
    $users = [];
    while ($res = $req->fetch()) {
        $res['user_is_online'] = time() <= $res['lastdate'] + 300;
        $res['album_url'] = '?act=list&amp;user=' . $res['uid'];
        $users[] = $res;
    }
}

$data['total'] = $total;
$data['pagination'] = $tools->displayPagination('?act=users' . ($mod ? '&amp;mod=' . $mod : '') . '&amp;', $start, $total, $user->config->kmess);
$data['users'] = $users ?? [];

echo $view->render(
    'album::users',
    [
        'title'      => $title,
        'page_title' => $title,
        'data'       => $data,
    ]
);
