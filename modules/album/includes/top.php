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
 * @var Johncms\System\Users\User $user
 */

$config = di('config')['johncms'];

$mod = isset($_GET['mod']) ? trim($_GET['mod']) : '';

switch ($mod) {
    case 'my_new_comm':
        // Непрочитанные комментарии в личных альбомах
        if (! $user->isValid() || $user->id != $foundUser['id']) {
            echo $tools->displayError(_t('Wrong data'));
            echo $view->render(
                'system::app/old_content',
                [
                    'title'   => $textl ?? '',
                    'content' => ob_get_clean(),
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

// Показываем список фотографий, отсортированных по рейтингу
unset($_SESSION['ref']);
echo '<div class="phdr"><a href="./"><b>' . _t('Photo Albums') . '</b></a> | ' . $title . '</div>';

if ($mod == 'my_new_comm') {
    $total = $new_album_comm; //TODO: разобраться
} elseif (! isset($total)) {
    $total = $db->query("SELECT COUNT(*) FROM `cms_album_files` WHERE ${where}")->fetchColumn();
}

if ($total) {
    if ($total > $user->config->kmess) {
        echo '<div class="topmenu">' . $tools->displayPagination('?act=top' . $link . '&amp;', $start, $total, $user->config->kmess) . '</div>';
    }

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

    for ($i = 0; $res = $req->fetch(); ++$i) {
        echo $i % 2 ? '<div class="list2">' : '<div class="list1">';

        if ($res['access'] == 4 || $user->rights >= 7) {
            // Если доступ открыт всем, или смотрит Администратор
            echo '<a href="?act=show&amp;al=' . $res['album_id'] . '&amp;img=' . $res['id'] . '&amp;user=' . $res['user_id'] . '&amp;view"><img src="../upload/users/album/' . $res['user_id'] . '/' . $res['tmb_name'] . '" /></a>';
            if (! empty($res['description'])) {
                echo '<div class="gray">' . $tools->smilies($tools->checkout($res['description'], 1)) . '</div>';
            }
        } elseif ($res['access'] == 3) {
            // Если доступ открыт друзьям
            echo 'Только для друзей';
        } elseif ($res['access'] == 2) {
            // Если доступ по паролю
            echo '<a href="?act=show&amp;al=' . $res['album_id'] . '&amp;img=' . $res['id'] . '&amp;user=' . $res['user_id'] . '"><img src="' . $config['homeurl'] . '/images/stop.gif" width="50" height="50"/></a>';
        }

        echo '<div class="sub">' .
            '<a href="?act=list&amp;user=' . $res['user_id'] . '"><b>' . $res['user_name'] . '</b></a> | <a href="?act=show&amp;al=' . $res['album_id'] . '&amp;user=' . $res['user_id'] . '">' . $tools->checkout($res['album_name']) . '</a>';

        if ($res['access'] == 4 || $user->rights >= 6) {
            echo vote_photo($res) .
                '<div class="gray">' . _t('Views') . ': ' . $res['views'] . ', ' . _t('Downloads') . ': ' . $res['downloads'] . '</div>' .
                '<div class="gray">' . _t('Date') . ': ' . $tools->displayDate($res['time']) . '</div>' .
                '<a href="?act=comments&amp;img=' . $res['id'] . '">' . _t('Comments') . '</a> (' . $res['comm_count'] . ')' .
                '<br><a href="?act=image_download&amp;img=' . $res['id'] . '">' . _t('Download') . '</a>';
        }

        echo '</div></div>';
    }
} else {
    echo '<div class="menu"><p>' . _t('The list is empty') . '</p></div>';
}

echo '<div class="phdr">' . _t('Total') . ': ' . $total . '</div>';

if ($total > $user->config->kmess) {
    echo '<div class="topmenu">' . $tools->displayPagination('?act=top' . $link . '&amp;', $start, $total, $user->config->kmess) . '</div>' .
        '<p><form action="?act=top' . $link . '" method="post">' .
        '<input type="text" name="page" size="2"/>' .
        '<input type="submit" value="' . _t('To Page') . ' &gt;&gt;"/>' .
        '</form></p>';
}
