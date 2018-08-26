<?php

/**
 * @package     JohnCMS
 * @link        http://johncms.com
 * @copyright   Copyright (C) 2008-2011 JohnCMS Community
 * @license     LICENSE.txt (see attached file)
 * @version     VERSION.txt (see attached file)
 * @author      http://johncms.com/about
 */

defined('_IN_JOHNCMS') or die('Error: restricted access');

switch ($mod) {
    case 'my_new_comm':
        /*
        -----------------------------------------------------------------
        Непрочитанные комментарии в личных альбомах
        -----------------------------------------------------------------
        */
        if (!core::$user_id || core::$user_id != $user['id']) {
            echo functions::display_error($lng['wrong_data']);
            require('../incfiles/end.php');
            exit;
        }
        $title = $lng_profile['unread_comments'];
        $select = "";
        $join = "INNER JOIN `cms_album_comments` ON `cms_album_files`.`id` = `cms_album_comments`.`sub_id`";
        $where = "`cms_album_files`.`user_id` = '" . core::$user_id . "' AND `cms_album_files`.`unread_comments` = 1 GROUP BY `cms_album_files`.`id`";
        $order = "`cms_album_comments`.`time` DESC";
        $link = '&amp;mod=my_new_comm';
        break;

    case 'last_comm':
        /*
        -----------------------------------------------------------------
        Последние комментарии по всем альбомам
        -----------------------------------------------------------------
        */
        $total = $db->query("SELECT COUNT(DISTINCT `sub_id`) FROM `cms_album_comments` WHERE `time` >" . (time() - 86400))->fetchColumn();
        $title = $lng_profile['new_comments'];
        $select = "";
        $join = "INNER JOIN `cms_album_comments` ON `cms_album_files`.`id` = `cms_album_comments`.`sub_id`";
        $where = "`cms_album_comments`.`time` > " . (time() - 86400) . " GROUP BY `cms_album_files`.`id`";
        $order = "`cms_album_comments`.`time` DESC";
        $link = '&amp;mod=last_comm';
        break;

    case 'views':
        /*
        -----------------------------------------------------------------
        ТОП просмотров
        -----------------------------------------------------------------
        */
        $title = $lng_profile['top_views'];
        $select = "";
        $join = "";
        $where = "`cms_album_files`.`views` > '0'" . (core::$user_rights >= 6 ? "" : " AND `cms_album_files`.`access` = '4'");
        $order = "`views` DESC";
        $link = '&amp;mod=views';
        break;

    case 'downloads':
        /*
        -----------------------------------------------------------------
        ТОП скачиваний
        -----------------------------------------------------------------
        */
        $title = $lng_profile['top_downloads'];
        $select = "";
        $join = "";
        $where = "`cms_album_files`.`downloads` > 0" . (core::$user_rights >= 6 ? "" : " AND `cms_album_files`.`access` = '4'");
        $order = "`downloads` DESC";
        $link = '&amp;mod=downloads';
        break;

    case 'comments':
        /*
        -----------------------------------------------------------------
        ТОП комментариев
        -----------------------------------------------------------------
        */
        $title = $lng_profile['top_comments'];
        $select = "";
        $join = "";
        $where = "`cms_album_files`.`comm_count` > '0'" . (core::$user_rights >= 6 ? "" : " AND `cms_album_files`.`access` = '4'");
        $order = "`comm_count` DESC";
        $link = '&amp;mod=comments';
        break;

    case 'votes':
        /*
        -----------------------------------------------------------------
        ТОП положительных голосов
        -----------------------------------------------------------------
        */
        $title = $lng_profile['top_votes'];
        $select = ", (`vote_plus` - `vote_minus`) AS `rating`";
        $join = "";
        $where = "(`vote_plus` - `vote_minus`) > 2" . (core::$user_rights >= 6 ? "" : " AND `cms_album_files`.`access` = '4'");
        $order = "`rating` DESC";
        $link = '&amp;mod=votes';
        break;

    case 'trash':
        /*
        -----------------------------------------------------------------
        ТОП отрицательных голосов
        -----------------------------------------------------------------
        */
        $title = $lng_profile['top_trash'];
        $select = ", (`vote_plus` - `vote_minus`) AS `rating`";
        $join = "";
        $where = "(`vote_plus` - `vote_minus`) < -2" . (core::$user_rights >= 6 ? "" : " AND `cms_album_files`.`access` = '4'");
        $order = "`rating` ASC";
        $link = '&amp;mod=trash';
        break;

    default:
        /*
        -----------------------------------------------------------------
        Новые изображения
        -----------------------------------------------------------------
        */
        $title = $lng_profile['new_photo'];
        $select = "";
        $join = "";
        $where = "`cms_album_files`.`time` > '" . (time() - 259200) . "'" . (core::$user_rights >= 6 ? "" : " AND `cms_album_files`.`access` = '4'");
        $order = "`cms_album_files`.`time` DESC";
        $link = '';
}

/*
-----------------------------------------------------------------
Показываем список фотографий, отсортированных по рейтингу
-----------------------------------------------------------------
*/
unset($_SESSION['ref']);
require('../incfiles/head.php');
echo '<div class="phdr"><a href="album.php"><b>' . $lng['photo_albums'] . '</b></a> | ' . $title . '</div>';

if ($mod == 'my_new_comm') {
    $total = $new_album_comm;
} elseif (!isset($total)) {
    $total = $db->query("SELECT COUNT(*) FROM `cms_album_files` WHERE $where")->fetchColumn();
}

if ($total) {
    if ($total > $kmess) {
        echo '<div class="topmenu">' . functions::display_pagination('album.php?act=top' . $link . '&amp;', $start, $total, $kmess) . '</div>';
    }

    $stmt = $db->query("
      SELECT `cms_album_files`.*, `users`.`name` AS `user_name`, `cms_album_cat`.`name` AS `album_name` $select
      FROM `cms_album_files`
      LEFT JOIN `users` ON `cms_album_files`.`user_id` = `users`.`id`
      LEFT JOIN `cms_album_cat` ON `cms_album_files`.`album_id` = `cms_album_cat`.`id`
      $join
      WHERE $where
      ORDER BY $order
      LIMIT $start, $kmess
    ");
    $i = 0;
    while ($res = $stmt->fetch()) {
        echo $i % 2 ? '<div class="list2">' : '<div class="list1">';

        if ($res['access'] == 4 || core::$user_rights >= 7) {
            // Если доступ открыт всем, или смотрит Администратор
            echo '<a href="album.php?act=show&amp;al=' . $res['album_id'] . '&amp;img=' . $res['id'] . '&amp;user=' . $res['user_id'] . '&amp;view"><img src="../files/users/album/' . $res['user_id'] . '/' . $res['tmb_name'] . '" /></a>';
            if (!empty($res['description']))
                echo '<div class="gray">' . functions::smileys(functions::checkout($res['description'], 1)) . '</div>';
        } elseif ($res['access'] == 3) {
            // Если доступ открыт друзьям
            echo 'Только для друзей';
        } elseif ($res['access'] == 2) {
            // Если доступ по паролю
            echo '<a href="album.php?act=show&amp;al=' . $res['album_id'] . '&amp;img=' . $res['id'] . '&amp;user=' . $res['user_id'] . '"><img src="' . core::$system_set['homeurl'] . '/images/stop.gif" width="50" height="50"/></a>';
        }

        echo '<div class="sub">' .
            '<a href="album.php?act=list&amp;user=' . $res['user_id'] . '"><b>' . $res['user_name'] . '</b></a> | <a href="album.php?act=show&amp;al=' . $res['album_id'] . '&amp;user=' . $res['user_id'] . '">' . functions::checkout($res['album_name']) . '</a>';

        if ($res['access'] == 4 || core::$user_rights >= 6) {
            echo vote_photo($res) .
                '<div class="gray">' . $lng['count_views'] . ': ' . $res['views'] . ', ' . $lng['count_downloads'] . ': ' . $res['downloads'] . '</div>' .
                '<div class="gray">' . $lng['date'] . ': ' . functions::display_date($res['time']) . '</div>' .
                '<a href="album.php?act=comments&amp;img=' . $res['id'] . '">' . $lng['comments'] . '</a> (' . $res['comm_count'] . ')' .
                '<br /><a href="album.php?act=image_download&amp;img=' . $res['id'] . '">' . $lng['download'] . '</a>';
        }

        echo '</div></div>';
    }
} else {
    echo '<div class="menu"><p>' . $lng['list_empty'] . '</p></div>';
}

echo '<div class="phdr">' . $lng['total'] . ': ' . $total . '</div>';

if ($total > $kmess) {
    echo '<div class="topmenu">' . functions::display_pagination('album.php?act=top' . $link . '&amp;', $start, $total, $kmess) . '</div>' .
        '<p><form action="album.php?act=top' . $link . '" method="post">' .
        '<input type="text" name="page" size="2"/>' .
        '<input type="submit" value="' . $lng['to_page'] . ' &gt;&gt;"/>' .
        '</form></p>';
}