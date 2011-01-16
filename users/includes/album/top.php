<?php

/*
////////////////////////////////////////////////////////////////////////////////
// JohnCMS                Mobile Content Management System                    //
// Project site:          http://johncms.com                                  //
// Support site:          http://gazenwagen.com                               //
////////////////////////////////////////////////////////////////////////////////
// Lead Developer:        Oleg Kasyanov   (AlkatraZ)  alkatraz@gazenwagen.com //
// Development Team:      Eugene Ryabinin (john77)    john77@gazenwagen.com   //
//                        Dmitry Liseenko (FlySelf)   flyself@johncms.com     //
////////////////////////////////////////////////////////////////////////////////
*/

defined('_IN_JOHNCMS') or die('Error: restricted access');
switch ($mod) {
    case 'views':
        /*
        -----------------------------------------------------------------
        ТОП просмотров
        -----------------------------------------------------------------
        */
        $title = $lng_profile['top_views'];
        $select = "";
        $where = "`cms_album_files`.`views` > '0'" . ($rights >= 6 ? "" : " AND `cms_album_files`.`access` = '4'");
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
        $where = "`cms_album_files`.`downloads` > '0'" . ($rights >= 6 ? "" : " AND `cms_album_files`.`access` = '4'");
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
        $where = "`cms_album_files`.`comm_count` > '0'" . ($rights >= 6 ? "" : " AND `cms_album_files`.`access` = '4'");
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
        $where = "(`vote_plus` - `vote_minus`) > 0" . ($rights >= 6 ? "" : " AND `cms_album_files`.`access` = '4'");
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
        $where = "(`vote_plus` - `vote_minus`) < 0" . ($rights >= 6 ? "" : " AND `cms_album_files`.`access` = '4'");
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
        $where = "`cms_album_files`.`time` > '" . ($realtime - 259200) . "'" . ($rights >= 6 ? "" : " AND `cms_album_files`.`access` > '1'");
        $order = "`cms_album_files`.`time` DESC";
        $link = '';
}

/*
-----------------------------------------------------------------
Показываем список фотографий, отсортированных по рейтингу
-----------------------------------------------------------------
*/
unset($_SESSION['ref']);
echo '<div class="phdr"><a href="album.php"><b>' . $lng['photo_albums'] . '</b></a> | ' . $title . '</div>';
$total = mysql_result(mysql_query("SELECT COUNT(*) FROM `cms_album_files` WHERE $where"), 0);
if ($total) {
    if ($total > $kmess)
        echo '<div class="topmenu">' . functions::display_pagination('album.php?act=top' . $link . '&amp;', $start, $total, $kmess) . '</div>';
    $req = mysql_query("SELECT `cms_album_files`.*, `users`.`name` AS `user_name`, `cms_album_cat`.`name` AS `album_name`$select
    FROM `cms_album_files`
    INNER JOIN `users` ON `cms_album_files`.`user_id` = `users`.`id`
    INNER JOIN `cms_album_cat` ON `cms_album_files`.`album_id` = `cms_album_cat`.`id`
    WHERE $where
    ORDER BY $order
    LIMIT $start, $kmess");
    while ($res = mysql_fetch_assoc($req)) {
        echo $i % 2 ? '<div class="list2">' : '<div class="list1">';
        if ($res['access'] == 4 || $rights >= 7) {
            // Если доступ открыт всем, или смотрит Администратор
            echo '<a href="album.php?act=show&amp;al=' . $res['album_id'] . '&amp;img=' . $res['id'] . '&amp;user=' . $res['user_id'] . '&amp;view"><img src="../../files/users/album/' . $res['user_id'] . '/' . $res['tmb_name'] . '" /></a>';
            if (!empty($res['description']))
                echo '<div class="gray">' . functions::smileys(functions::checkout($res['description'], 1)) . '</div>';
        } elseif ($res['access'] == 3) {
            // Если доступ открыт друзьям
            echo 'Только для друзей';
        } elseif ($res['access'] == 2) {
            // Если доступ по паролю
            echo '<a href="album.php?act=show&amp;al=' . $res['album_id'] . '&amp;img=' . $res['id'] . '&amp;user=' . $res['user_id'] . '"><img src="' . $set['homeurl'] . '/images/stop.gif" width="50" height="50"/></a>';
        }
        echo '<div class="sub">';
        echo '<a href="album.php?act=list&amp;user=' . $res['user_id'] . '"><b>' . $res['user_name'] . '</b></a> | <a href="album.php?act=show&amp;al=' . $res['album_id'] . '&amp;user=' . $res['user_id'] . '">' . functions::checkout($res['album_name']) . '</a>';
        if ($res['access'] == 4 || $rights >= 6) {
            echo vote_photo($res) .
                '<div class="gray">' . $lng['count_views'] . ': ' . $res['views'] . ', ' . $lng['count_downloads'] . ': ' . $res['downloads'] . '</div>' .
                '<div class="gray">' . $lng['date'] . ': ' . date("d.m.Y / H:i", $res['time'] + $set_user['sdvig'] * 3600) . '</div>' .
                '<a href="album.php?act=comments&amp;img=' . $res['id'] . '">' . $lng['comments'] . '</a> (' . $res['comm_count'] . ')' .
                '<br /><a href="album.php?act=image_download&amp;img=' . $res['id'] . '">' . $lng['download'] . '</a>';
        }
        echo '</div></div>';
        ++$i;
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
?>