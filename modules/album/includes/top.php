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
 * @var Johncms\System\Utility\Tools $tools
 * @var Johncms\System\Users\User $user
 * @var Johncms\System\Http\Request $request
 */

$config = di('config')['johncms'];

$mod = $request->getQuery('mod', '', FILTER_SANITIZE_STRING);

switch ($mod) {
    case 'my_new_comm':
        // Непрочитанные комментарии в личных альбомах
        if ($user->id !== $foundUser['id'] || ! $user->isValid()) {
            echo $view->render(
                'system::pages/result',
                [
                    'title'   => $title,
                    'type'    => 'alert-danger',
                    'message' => _t('Wrong data'),
                ]
            );
            exit;
        }

        $title = _t('Unread Comments');
        $select = '';
        $join = 'INNER JOIN `cms_album_comments` ON `cms_album_files`.`id` = `cms_album_comments`.`sub_id`';
        $where = "`cms_album_files`.`user_id` = '" . $user->id . "' AND `cms_album_files`.`unread_comments` = 1 GROUP BY `cms_album_files`.`id`";
        $order = '`cms_album_comments`.`time` DESC';
        $link = '&amp;mod=my_new_comm';
        break;

    case 'last_comm':
        // Последние комментарии по всем альбомам
        $total = $db->query('SELECT COUNT(DISTINCT `sub_id`) FROM `cms_album_comments` WHERE `time` >' . (time() - 86400))->fetchColumn();
        $title = _t('Recent comments');
        $select = '';
        $join = 'INNER JOIN `cms_album_comments` ON `cms_album_files`.`id` = `cms_album_comments`.`sub_id`';
        $where = '`cms_album_comments`.`time` > ' . (time() - 86400) . ' GROUP BY `cms_album_files`.`id`';
        $order = '`cms_album_comments`.`time` DESC';
        $link = '&amp;mod=last_comm';
        break;

    case 'views':
        // ТОП просмотров
        $title = _t('Top Views');
        $select = '';
        $join = '';
        $where = "`cms_album_files`.`views` > '0'" . ($user->rights >= 6 ? '' : " AND `cms_album_files`.`access` = '4'");
        $order = '`views` DESC';
        $link = '&amp;mod=views';
        break;

    case 'downloads':
        // ТОП скачиваний
        $title = _t('Top Downloads');
        $select = '';
        $join = '';
        $where = '`cms_album_files`.`downloads` > 0' . ($user->rights >= 6 ? '' : " AND `cms_album_files`.`access` = '4'");
        $order = '`downloads` DESC';
        $link = '&amp;mod=downloads';
        break;

    case 'comments':
        // ТОП комментариев
        $title = _t('Top Comments');
        $select = '';
        $join = '';
        $where = "`cms_album_files`.`comm_count` > '0'" . ($user->rights >= 6 ? '' : " AND `cms_album_files`.`access` = '4'");
        $order = '`comm_count` DESC';
        $link = '&amp;mod=comments';
        break;

    case 'votes':
        // ТОП положительных голосов
        $title = _t('Top Votes');
        $select = ', (`vote_plus` - `vote_minus`) AS `rating`';
        $join = '';
        $where = '(`vote_plus` - `vote_minus`) > 2' . ($user->rights >= 6 ? '' : " AND `cms_album_files`.`access` = '4'");
        $order = '`rating` DESC';
        $link = '&amp;mod=votes';
        break;

    case 'trash':
        // ТОП отрицательных голосов
        $title = _t('Top Worst');
        $select = ', (`vote_plus` - `vote_minus`) AS `rating`';
        $join = '';
        $where = '(`vote_plus` - `vote_minus`) < -2' . ($user->rights >= 6 ? '' : " AND `cms_album_files`.`access` = '4'");
        $order = '`rating` ASC';
        $link = '&amp;mod=trash';
        break;

    default:
        // Новые изображения
        $title = _t('New photos');
        $select = '';
        $join = '';
        $where = "`cms_album_files`.`time` > '" . (time() - 259200) . "'" . ($user->rights >= 6 ? '' : " AND `cms_album_files`.`access` = '4'");
        $order = '`cms_album_files`.`time` DESC';
        $link = '';
}

$data = [];

// Показываем список фотографий, отсортированных по рейтингу
unset($_SESSION['ref']);

if ($mod === 'my_new_comm') {
    $total = $db->query('SELECT COUNT(*) FROM `cms_album_files` WHERE `user_id` = ' . $user->id . ' AND `unread_comments` = 1')->fetchColumn();
} elseif (! isset($total)) {
    $total = $db->query("SELECT COUNT(*) FROM `cms_album_files` WHERE ${where}")->fetchColumn();
}

$photos = [];
if ($total) {
    $req = $db->query(
        "
      SELECT `cms_album_files`.*, `users`.`name` AS `user_name`, `cms_album_cat`.`name` AS `album_name` ${select}
      FROM `cms_album_files`
      LEFT JOIN `users` ON `cms_album_files`.`user_id` = `users`.`id`
      LEFT JOIN `cms_album_cat` ON `cms_album_files`.`album_id` = `cms_album_cat`.`id`
      ${join}
      WHERE ${where}
      ORDER BY ${order}
      LIMIT ${start}, " . $user->config->kmess
    );

    while ($res = $req->fetch()) {
        $photos[] = new Photo($res);
    }
}

$data['photos'] = $photos;
$data['total'] = $total;
$data['pagination'] = $tools->displayPagination('?act=top' . $link . '&amp;', $start, $total, $user->config->kmess);

$nav_chain->add($title);

echo $view->render(
    'album::top',
    [
        'title'      => $title,
        'page_title' => $title,
        'data'       => $data,
    ]
);
