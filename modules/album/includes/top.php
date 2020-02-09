<?php

/**
 * This file is part of JohnCMS Content Management System.
 *
 * @copyright JohnCMS Community
 * @license   https://opensource.org/licenses/GPL-3.0 GPL-3.0
 * @link      https://johncms.com JohnCMS Project
 */

declare(strict_types=1);

use Albums\Photo;

defined('_IN_JOHNCMS') || die('Error: restricted access');

/**
 * @var PDO $db
 * @var Johncms\System\Legacy\Tools $tools
 * @var Johncms\System\Users\User $user
 * @var Johncms\System\Http\Request $request
 */

$config = di('config')['johncms'];

$mod = $request->getQuery('mod', '', FILTER_SANITIZE_STRING);

$user_right = ($user->rights >= 6 ? '' : ' AND (files.`access` = 4 OR files.`user_id` = ' . $user->id . ')');

switch ($mod) {
    case 'my_new_comm':
        // Непрочитанные комментарии в личных альбомах
        if ($user->id !== $foundUser['id'] || ! $user->isValid()) {
            echo $view->render(
                'system::pages/result',
                [
                    'title'   => $title,
                    'type'    => 'alert-danger',
                    'message' => __('Wrong data'),
                ]
            );
            exit;
        }

        $title = __('Unread Comments');
        $pattern = 'SELECT
    files.*,
    cat.`name` AS albumname,
    u.`name` AS user_name, (
SELECT MAX(`time`) FROM cms_album_comments WHERE sub_id = files.id) AS mtime
FROM cms_album_files files
JOIN cms_album_cat cat ON cat.id = files.album_id
JOIN users u ON u.id = files.user_id
WHERE files.user_id = ' . $user->id . ' AND unread_comments = 1
ORDER BY mtime DESC LIMIT ' . $start . ', ' . $user->config->kmess;
        $where = "`cms_album_files`.`user_id` = '" . $user->id . "' AND `cms_album_files`.`unread_comments` = 1 GROUP BY `cms_album_files`.`id`";
        $link = '?mod=my_new_comm';
        break;

    case 'last_comm':
        // Последние комментарии по всем альбомам
        $total = $db->query(
            'SELECT COUNT(DISTINCT `comm`.`sub_id`) FROM `cms_album_comments` comm
JOIN `cms_album_files` files ON `files`.`id`=`comm`.`sub_id` WHERE `comm`.`time` >' . (time() - 86400) . '' . $user_right
        )->fetchColumn();
        $title = __('Recent comments');
        $pattern = 'SELECT
    `files`.*,
    `u`.`name` AS `user_name`,
    `cat`.`name` AS `album_name`
FROM `cms_album_files` files
JOIN `users` u ON `files`.`user_id` = `u`.`id`
JOIN `cms_album_cat` cat ON `files`.`album_id` = `cat`.`id`' . $user_right . '
JOIN `cms_album_comments` comm ON `files`.`id` = `comm`.`sub_id`
JOIN (
SELECT `sub_id`, max(`time`) as `mtime` FROM `cms_album_comments` WHERE `time` > ' . (time() - 86400) . ' GROUP BY `sub_id`) as comm2
ON comm.`sub_id`= comm2.`sub_id` AND comm.`time` = comm2.`mtime`
ORDER BY mtime DESC LIMIT ' . $start . ', ' . $user->config->kmess;
        $link = '?mod=last_comm';
        break;

    case 'views':
        // ТОП просмотров
        $title = __('Top Views');
        $pattern = 'SELECT
    `files`.*,
    `u`.`name` AS `user_name`,
    `cat`.`name` AS `album_name`
FROM `cms_album_files` files
JOIN `users` u ON `files`.`user_id` = `u`.`id`
JOIN `cms_album_cat` cat ON `files`.`album_id` = `cat`.`id`
WHERE `files`.`views` > 0 ' . $user_right . '
ORDER BY `views` DESC,  `downloads` DESC LIMIT ' . $start . ', ' . $user->config->kmess;
        $where = "`files`.`views` > '0'" . $user_right;
        $link = '?mod=views';
        break;

    case 'downloads':
        // ТОП скачиваний
        $title = __('Top Downloads');
        $pattern = 'SELECT
    `files`.*,
    `u`.`name` AS `user_name`,
    `cat`.`name` AS `album_name`
FROM `cms_album_files` files
JOIN `users` u ON `files`.`user_id` = `u`.`id`
JOIN `cms_album_cat` cat ON `files`.`album_id` = `cat`.`id`
WHERE `files`.`downloads` > 0 ' . $user_right . '
ORDER BY `downloads` DESC, `views` DESC LIMIT ' . $start . ', ' . $user->config->kmess;
        $where = '`files`.`downloads` > 0' . $user_right;
        $link = '?mod=downloads';
        break;

    case 'comments':
        // ТОП комментариев
        $title = __('Top Comments');
        $pattern = 'SELECT
    `files`.*,
    `u`.`name` AS `user_name`,
    `cat`.`name` AS `album_name`
FROM `cms_album_files` files
JOIN `users` u ON `files`.`user_id` = `u`.`id`
JOIN `cms_album_cat` cat ON `files`.`album_id` = `cat`.`id`
WHERE `files`.`comm_count` > 0 ' . $user_right . '
ORDER BY `comm_count` DESC, `views` DESC LIMIT ' . $start . ', ' . $user->config->kmess;
        $where = "`files`.`comm_count` > '0'" . $user_right;
        $link = '?mod=comments';
        break;

    case 'votes':
        // ТОП положительных голосов
        $title = __('Top Votes');
        $pattern = 'SELECT
    `files`.*,
    `u`.`name` AS `user_name`,
    `cat`.`name` AS `album_name`,
    (`vote_plus` - `vote_minus`) AS `rating`
FROM `cms_album_files` files
JOIN `users` u ON `files`.`user_id` = `u`.`id`
JOIN `cms_album_cat` cat ON `files`.`album_id` = `cat`.`id`
WHERE (`vote_plus` - `vote_minus`) > 2 ' . $user_right . '
ORDER BY `rating` DESC, `views` DESC LIMIT ' . $start . ', ' . $user->config->kmess;
        $where = '(`vote_plus` - `vote_minus`) > 2' . $user_right;
        $link = '?mod=votes';
        break;

    case 'trash':
        // ТОП отрицательных голосов
        $title = __('Top Worst');
        $pattern = 'SELECT
    `files`.*,
    `u`.`name` AS `user_name`,
    `cat`.`name` AS `album_name`,
    (`vote_plus` - `vote_minus`) AS `rating`
FROM `cms_album_files` files
JOIN `users` u ON `files`.`user_id` = `u`.`id`
JOIN `cms_album_cat` cat ON `files`.`album_id` = `cat`.`id`
WHERE (`vote_plus` - `vote_minus`) < (-2) ' . $user_right . '
ORDER BY `rating` ASC, `views` ASC LIMIT ' . $start . ', ' . $user->config->kmess;
        $where = '(`vote_plus` - `vote_minus`) < (-2)' . $user_right;
        $link = '?mod=trash';
        break;

    default:
        // Новые изображения
        $title = __('New photos');
        $pattern = 'SELECT
    `files`.*,
    `u`.`name` AS `user_name`,
    `cat`.`name` AS `album_name`
FROM `cms_album_files` files
JOIN `users` u ON `files`.`user_id` = `u`.`id`
JOIN `cms_album_cat` cat ON `files`.`album_id` = `cat`.`id`
WHERE `files`.`time` > ' . (time() - 259200) . ' ' . $user_right . '
ORDER BY `files`.`time` DESC LIMIT ' . $start . ', ' . $user->config->kmess;
        $where = "`files`.`time` > '" . (time() - 259200) . "'" . $user_right;
        $link = '';
}

$data = [];

// Показываем список фотографий, отсортированных по рейтингу
unset($_SESSION['ref']);

if ($mod === 'my_new_comm') {
    $total = $db->query('SELECT COUNT(*) FROM `cms_album_files` WHERE `user_id` = ' . $user->id . ' AND `unread_comments` = 1')->fetchColumn();
} elseif (! isset($total)) {
    $total = $db->query("SELECT COUNT(*) FROM `cms_album_files` files WHERE ${where}")->fetchColumn();
}

$photos = [];
if ($total) {
    $req = $db->prepare($pattern);
    $req->execute();

    while ($res = $req->fetch()) {
        $photos[] = new Photo($res);
    }
}

$data['photos'] = $photos;
$data['total'] = $total;
$data['pagination'] = $tools->displayPagination('./top' . (! empty($link) ? $link . '&amp;' : '?'), $start, $total, $user->config->kmess);

$nav_chain->add($title);

echo $view->render(
    'album::top',
    [
        'title'      => $title,
        'page_title' => $title,
        'data'       => $data,
    ]
);
