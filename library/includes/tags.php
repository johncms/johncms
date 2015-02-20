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

$obj = new Hashtags(0);

if (isset($_GET['tag'])) {
    $tag = urldecode($_GET['tag']);
    if ($obj->get_all_tag_stats($tag)) {
        $total = sizeof($obj->get_all_tag_stats($tag));                                                                       
        $page = $page >= ceil($total / $kmess) ? ceil($total / $kmess) : $page;
        $start = $page == 1 ? 0 : ($page - 1) * $kmess;    

        echo '<div class="phdr"><a href="?"><b>' . $lng['library'] . '</b></a> | ' . $lng_lib['tags'] . '</div>';
        
        if ($total > $kmess) {
            echo '<div class="topmenu">' . functions::display_pagination('?act=tags&amp;tag=' . urlencode($tag) . '&amp;', $start, $total, $kmess) . '</div>';            
        }
        
        foreach (new LimitIterator(new ArrayIterator($obj->get_all_tag_stats($tag)), $start, $kmess) as $txt) {
            $row = mysql_fetch_assoc(mysql_query("SELECT `id`, `name`, `time`, `author`, `count_views`, `count_comments`, `comments` FROM `library_texts` WHERE `id` = " . $txt));
            $obj = new Hashtags($row['id']);
            echo '<div class="list' . (++$i % 2 ? 2 : 1) . '">'
            . (file_exists('../files/library/images/small/' . $row['id'] . '.png') 
            ? '<div class="avatar"><img src="../files/library/images/small/' . $row['id'] . '.png" alt="screen" /></div>'
            : '')
            . '<div class="righttable"><a href="?do=text&amp;id=' . $row['id'] . '">' . $row['name'] . '</a>'
            . '<div>' . bbcode::notags(mysql_result(mysql_query("SELECT SUBSTRING(`text`, 1 , 200) FROM `library_texts` WHERE `id`=" . $row['id']) , 0)) . '</div></div>'
            . '<div class="sub">' . $lng_lib['added'] . ': ' . $row['author'] . ' (' . functions::display_date($row['time']) . ')</div>'
            . '<div><span class="gray">' . $lng_lib['reads'] . ':</span> ' . $row['count_views'] . '</div>'
            . '<div>' . ($obj->get_all_stat_tags() ? 'Теги [ ' . $obj->get_all_stat_tags(1) . ' ]' : '') . '</div>'
            . ($row['comments'] ? '<div><a href="?act=comments&amp;id=' . $row['id'] . '">' . $lng['comments'] . '</a> (' . $row['count_comments'] . ')</div>' : '')
            . '</div>';
        }
        
        echo '<div class="phdr">' . $lng['total'] . ': ' . intval($total) . '</div>';
        
        if ($total > $kmess) {
            echo '<div class="topmenu">' . functions::display_pagination('?act=tags&amp;tag=' . urlencode($tag) . '&amp;', $start, $total, $kmess) . '</div>';            
        }
        echo '<div><a href="?">' . $lng_lib['to_library'] . '</a></div>' . PHP_EOL;
    }
} else {
    redir404();
}