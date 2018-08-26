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

echo '<div class="phdr"><strong><a href="?">' . $lng['library'] . '</a></strong> | ' . $lng_lib['new_articles'] . '</div>';

$total = $db->query("SELECT COUNT(*) FROM `library_texts` WHERE `time` > '" . (time() - 259200) . "' AND `premod`=1")->fetchColumn();
$page = $page >= ceil($total / $kmess) ? ceil($total / $kmess) : $page;
$start = $page == 1 ? 0 : ($page - 1) * $kmess;
$stmt = $db->query("SELECT `id`, `name`, `time`, `uploader`, `uploader_id`, `count_views`, `comments`, `count_comments`, `cat_id`, `announce` FROM `library_texts` WHERE `time` > '" . (time() - 259200) . "' AND `premod`=1 ORDER BY `time` DESC LIMIT " . $start . "," . $kmess);
$nav = ($total > $kmess) ? '<div class="topmenu">' . functions::display_pagination('?act=new&amp;', $start, $total, $kmess) . '</div>' : '';
echo $nav;
if ($total) {
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
            . '<td><a href="' . core::$system_set['homeurl'] . '/users/profile.php?user=' . $row['uploader_id'] . '">' . functions::checkout($row['uploader']) . '</a> (' . functions::display_date($row['time']) . ')</td>'
            . '</tr>'
            // Рейтинг
            . '<tr>'
            . '<td class="caption">' . $lng['rating'] . ':</td>'
            . '<td>' . $rate->view_rate() . '</td>'
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
echo '<div class="phdr">' . $lng['total'] . ': ' . intval($total) . '</div>';
echo $nav;
echo '<p><a href="?">' . $lng_lib['to_library'] . '</a></p>';