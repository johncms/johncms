<?php
/**
 * @package     JohnCMS
 * @link        http://johncms.com
 * @copyright   Copyright (C) 2008-2015 JohnCMS Community
 * @license     LICENSE.txt (see attached file)
 * @version     VERSION.txt (see attached file)
 * @author      http://johncms.com/about
 */

defined('_IN_JOHNCMS') or die('Error: restricted access');

$sort = isset($_GET['sort']) && $_GET['sort'] == 'rating' ? 'rating' : (isset($_GET['sort']) && $_GET['sort'] == 'comm' ? 'comm': 'read');

$menu[] = $sort == 'read' ? '<strong>' . $lng_lib['by_reading'] . '</strong>' : '<a href="?act=top&amp;sort=read">' . $lng_lib['by_reading'] . '</a> ';
$menu[] = $sort == 'rating' ? '<strong>' . $lng_lib['by_rating'] . '</strong>' : '<a href="?act=top&amp;sort=rating">' . $lng_lib['by_rating'] . '</a> ';
$menu[] = $sort == 'comm' ? '<strong>' . $lng_lib['by_comments'] . '</strong>' : '<a href="?act=top&amp;sort=comm">' . $lng_lib['by_comments'] . '</a>';


echo '<div class="phdr"><strong><a href="?">' . $lng['library'] . '</a></strong> | ' . $lng_lib['rated_articles'] . '</div>' .
     '<div class="topmenu">' . $lng_lib['sort'] . ': ' . functions::display_menu($menu) . '</div>';

if ($sort == 'read' || $sort == 'comm') {
    $total = $db->query('SELECT COUNT(*) FROM `library_texts` WHERE ' . ($sort == 'comm' ? '`count_comments`' : '`count_views`') . ' > 0 ORDER BY ' . ($sort == 'comm' ? '`count_comments`' : '`count_views`') . ' DESC LIMIT 20')->fetchColumn();
} else {
    $total = $db->query("SELECT COUNT(*) AS `cnt`, AVG(`point`) AS `avg` FROM `cms_library_rating` GROUP BY `st_id` ORDER BY `avg` DESC, `cnt` DESC LIMIT 20")->fetchColumn();
}

$page = $page >= ceil($total / $kmess) ? ceil($total / $kmess) : $page;
$start = $page == 1 ? 0 : ($page - 1) * $kmess;
if (!$total) {
    echo '<div class="menu"><p>' . $lng['list_empty'] . '</p></div>';
} else {
    if ($sort == 'read' || $sort == 'comm') {
        $stmt = $db->query('SELECT `id`, `name`, `time`, `uploader`, `uploader_id`, `count_views`, `cat_id`, `comments`, `count_comments`, `announce` FROM `library_texts` WHERE ' . ($sort == 'comm' ? '`count_comments`' : '`count_views`') . ' > 0 ORDER BY ' . ($sort == 'comm' ? '`count_comments`' : '`count_views`') . ' DESC LIMIT ' . $start . ',' . $kmess);
    } else {
        $stmt = $db->query("SELECT `library_texts`.*, COUNT(*) AS `cnt`, AVG(`point`) AS `avg` FROM `cms_library_rating` JOIN `library_texts` ON `cms_library_rating`.`st_id` = `library_texts`.`id` GROUP BY `cms_library_rating`.`st_id` ORDER BY `avg` DESC, `cnt` DESC LIMIT " . $start . ',' . $kmess);
    }

    $i = 0;
    while ($row = $stmt->fetch()) {
        echo '<div class="list' . (++$i % 2 ? 2 : 1) . '">'
            . (file_exists('../files/library/images/small/' . $row['id'] . '.png')
                ? '<div class="avatar"><img src="../files/library/images/small/' . $row['id'] . '.png" alt="screen" /></div>'
                : '')
            . '<div class="righttable"><h4><a href="index.php?id=' . $row['id'] . '">' . functions::checkout($row['name']) . '</a></h4>'
            . '<div><small>' . functions::checkout(bbcode::notags($row['announce'])) . '</small></div></div>';

        // Описание к статье
        $obj = new Hashtags($row['id']);
        $rate = new Rating($row['id']);
        $uploader = $row['uploader_id'] ? '<a href="' . core::$system_set['homeurl'] . '/users/profile.php?user=' . $row['uploader_id'] . '">' . functions::checkout($row['uploader']) . '</a>' : functions::checkout($row['uploader']);
        echo '<table class="desc">'
            // Раздел
            . '<tr>'
            . '<td class="caption">' . $lng['section'] . ':</td>'
            . '<td><a href="?do=dir&amp;id=' . $row['cat_id'] . '">' . functions::checkout($db->query("SELECT `name` FROM `library_cats` WHERE `id`=" . $row['cat_id'])->fetchColumn()) . '</a></td>'
            . '</tr>'
            // Тэги
            . ($obj->get_all_stat_tags() ? '<tr><td class="caption">' . $lng_lib['tags'] . ':</td><td>' . $obj->get_all_stat_tags(1) . '</td></tr>' : '')
            // Кто добавил?
            . '<tr>'
            . '<td class="caption">' . $lng_lib['added'] . ':</td>'
            . '<td>' . $uploader . ' (' . functions::display_date($row['time']) . ')</td>'
            . '</tr>'
            // Рейтинг
            . '<tr>'
            . '<td class="caption">' . $lng['rating'] . ':</td>'
            . '<td>' . $rate->view_rate(1) . '</td>'
            . '</tr>'
            // Прочтений
            . '<tr>'
            . '<td class="caption">' . $lng_lib['reads'] . ':</td>'
            . '<td>' . $row['count_views'] . '</td>'
            . '</tr>'
            // Комментарии
            . '<tr>';
        if ($row['comments']) {
            echo '<td class="caption"><a href="?act=comments&amp;id=' . $row['id'] . '">' . $lng['comments'] . '</a>:</td><td>' . $row['count_comments'] . '</td>';
        } else {
            echo '<td class="caption">' . $lng['comments'] . ':</td><td>' . $lng['comments_closed'] . '</td>';
        }
        echo '</tr></table>';

        echo '</div>';
    }
}

echo '<div class="phdr"><a href="?">' . $lng['back'] . '</a></div>';
