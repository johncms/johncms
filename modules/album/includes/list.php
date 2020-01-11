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
 * @var Johncms\System\Users\User $user
 */

// Список альбомов юзера
if (isset($_SESSION['ap'])) {
    unset($_SESSION['ap']);
}

$data = [];

$req = $db->query(
    "SELECT cms_album_cat.*, (SELECT COUNT(*) FROM `cms_album_files` WHERE `album_id` = cms_album_cat.id) as count_photos
FROM `cms_album_cat`
WHERE `user_id` = '" . $foundUser['id'] . "' " . ($foundUser['id'] === $user->id || $user->rights >= 6 ? '' : 'AND `access` > 1') . '
ORDER BY `sort`'
);
$total = $req->rowCount();

$data['create_url'] = '';
if (($foundUser['id'] === $user->id && $total < $max_album && empty($user->ban)) || $user->rights >= 7) {
    $data['create_url'] = './edit?user=' . $foundUser['id'];
}

$foundUser['nick'] = $foundUser['name'];
$foundUser['album_url'] = '/profile/?user=' . $foundUser['id'];
$foundUser['user_is_online'] = time() <= $foundUser['lastdate'] + 300;

$data['user'] = $foundUser;
$data['total'] = $total;

$albums = [];
if ($total) {
    while ($res = $req->fetch()) {
        $res['name'] = $tools->checkout($res['name']);
        $res['description'] = $tools->checkout($res['description'], 1, 1);
        $res['album_url'] = './show?al=' . $res['id'] . '&amp;user=' . $foundUser['id'];

        $res['has_edit'] = false;
        if (($foundUser['id'] === $user->id && empty($user->ban)) || $user->rights >= 6) {
            $res['up_url'] = './sort?mod=up&amp;al=' . $res['id'] . '&amp;user=' . $foundUser['id'];
            $res['down_url'] = './sort?mod=down&amp;al=' . $res['id'] . '&amp;user=' . $foundUser['id'];
            $res['edit_url'] = './edit?al=' . $res['id'] . '&amp;user=' . $foundUser['id'];
            $res['delete_url'] = './delete?al=' . $res['id'] . '&amp;user=' . $foundUser['id'];
            $res['has_edit'] = true;
        }
        $albums[] = $res;
    }
}

$count_photos = array_column($albums, 'count_photos');
$data['total_photos'] = array_sum($count_photos);
$data['albums'] = $albums;

$data['user']['count_albums'] = $total;
$data['user']['count'] = $data['total_photos'];

if ($user->id === $data['user']['id']) {
    $title = __('Your albums');
    $nav_chain->add($title);
} else {
    $title = __('User albums:') . ' ' . $data['user']['name'];
    $nav_chain->add(__('User albums'));
}

echo $view->render(
    'album::list',
    [
        'title'      => $title,
        'page_title' => $title,
        'data'       => $data,
    ]
);
