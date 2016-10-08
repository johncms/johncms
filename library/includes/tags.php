<?php

defined('_IN_JOHNCMS') or die('Error: restricted access');

$obj = new Hashtags(0);

if (isset($_GET['tag'])) {
    $tag = urldecode($_GET['tag']);
    if ($obj->get_all_tag_stats($tag)) {
        $total = sizeof($obj->get_all_tag_stats($tag));                                                                       
        $page = $page >= ceil($total / $kmess) ? ceil($total / $kmess) : $page;
        $start = $page == 1 ? 0 : ($page - 1) * $kmess;    

        echo '<div class="phdr"><a href="?"><strong>' . _t('Library') . '</strong></a> | ' . _t('Tags') . '</div>';
        
        if ($total > $kmess) {
            echo '<div class="topmenu">' . functions::display_pagination('?act=tags&amp;tag=' . urlencode($tag) . '&amp;', $start, $total, $kmess) . '</div>';            
        }
        
        foreach (new LimitIterator(new ArrayIterator($obj->get_all_tag_stats($tag)), $start, $kmess) as $txt) {
            $row = $db->query("SELECT `id`, `name`, `time`, `uploader`, `uploader_id`, `count_views`, `comm_count`, `comments` FROM `library_texts` WHERE `id` = " . $txt)->fetch();
            $obj = new Hashtags($row['id']);
            echo '<div class="list' . (++$i % 2 ? 2 : 1) . '">'
            . (file_exists('../files/library/images/small/' . $row['id'] . '.png') 
            ? '<div class="avatar"><img src="../files/library/images/small/' . $row['id'] . '.png" alt="screen" /></div>'
            : '')
            . '<div class="righttable"><a href="index.php?id=' . $row['id'] . '">' . functions::checkout($row['name']) . '</a>'
            . '<div>' . functions::checkout(bbcode::notags($db->query("SELECT SUBSTRING(`text`, 1 , 200) FROM `library_texts` WHERE `id`=" . $row['id'])->fetchColumn())) . '</div></div>'
            . '<div class="sub">' . _t('Who added') . ': ' . '<a href="' . core::$system_set['homeurl'] . '/profile/?user=' . $row['uploader_id'] . '">' . functions::checkout($row['uploader']) . '</a>' . ' (' . functions::display_date($row['time']) . ')</div>'
            . '<div><span class="gray">' . _t('Number of readings') . ':</span> ' . $row['count_views'] . '</div>'
            . '<div>' . ($obj->get_all_stat_tags() ? _t('Tags') . ' [ ' . $obj->get_all_stat_tags(1) . ' ]' : '') . '</div>'
            . ($row['comments'] ? '<div><a href="?act=comments&amp;id=' . $row['id'] . '">' . _t('Comments') . '</a> (' . $row['comm_count'] . ')</div>' : '')
            . '</div>';
        }
        
        echo '<div class="phdr">' . _t('Total') . ': ' . intval($total) . '</div>';
        
        if ($total > $kmess) {
            echo '<div class="topmenu">' . functions::display_pagination('?act=tags&amp;tag=' . urlencode($tag) . '&amp;', $start, $total, $kmess) . '</div>';            
        }
        echo '<p><a href="?">' . _t('To Library') . '</a></p>';
    }
} else {
    redir404();
}