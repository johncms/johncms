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

$title = _t('List of users');
$nav_chain->add($title);

// Список посетителей. у которых есть фотографии
switch ($mod) {
    case 'boys':
        $sql = "WHERE `u`.`sex` = 'm'";
        break;

    case 'girls':
        $sql = "WHERE `u`.`sex` = 'zh'";
        break;
    default:
        $sql = "WHERE `u`.`sex` != ''";
}

$data = [];
$data['filters'] = [
    'all'   => [
        'name'   => _t('All'),
        'url'    => '?./users',
        'active' => ! $mod,
    ],
    'boys'  => [
        'name'   => _t('Guys'),
        'url'    => '?./users?mod=boys',
        'active' => $mod === 'boys',
    ],
    'girls' => [
        'name'   => _t('Girls'),
        'url'    => '?./users?mod=girls',
        'active' => $mod === 'girls',
    ],
];

$total = $db->query(
    "SELECT COUNT(DISTINCT `user_id`)
    FROM `cms_album_files`
    LEFT JOIN `users` u ON `cms_album_files`.`user_id` = `u`.`id` ${sql}
"
)->fetchColumn();

if ($total) {
    $album_access = ($foundUser['id'] === $user->id || $user->rights >= 6 ? '' : ' AND albums.access > 1');
    $req = $db->query("SELECT distinct(`a`.user_id) AS id, `u`.`lastdate`,`u`.`name` AS `nick`, (
SELECT COUNT(*) FROM `cms_album_files` WHERE `user_id` = `u`.`id`) AS `count`, (
SELECT COUNT(*) FROM `cms_album_cat` AS albums WHERE `albums`.`user_id` = `u`.`id` ${album_access}) AS count_albums
FROM `cms_album_files` a
LEFT JOIN `users` u ON `a`.`user_id` = `u`.`id` ${sql}
ORDER BY `u`.`name` ASC LIMIT ${start}, " . $user->config->kmess);
    $users = [];
    while ($res = $req->fetch()) {
        $res['user_is_online'] = time() <= $res['lastdate'] + 300;
        $res['album_url'] = '?./list?user=' . $res['id'];
        $users[] = $res;
    }
}

$data['total'] = $total;
$data['pagination'] = $tools->displayPagination('?./users' . ($mod ? '?mod=' . $mod : '') . '&amp;', $start, $total, $user->config->kmess);
$data['users'] = $users ?? [];

echo $view->render(
    'album::users',
    [
        'title'      => $title,
        'page_title' => $title,
        'data'       => $data,
    ]
);
