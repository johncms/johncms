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

echo '<div class="phdr"><strong><a href="?">' . $lng['library'] . '</a></strong> | ' . $lng_lib['rated_articles'] . '</div>'
. '<div class="bmenu">' . $lng_lib['sort'] . ' <a href="?act=top&amp;sort=read">' . $lng_lib['by_reading'] . '</a> | <a href="?act=top&amp;sort=rating">' . $lng_lib['by_rating'] . '</a></div>';

$sort = isset($_GET['sort']) && $_GET['sort'] == 'rating' ? 'rating' : 'read';

if ($sort == 'read') {
    $total = mysql_result(mysql_query('SELECT COUNT(*) FROM `library_texts` WHERE `count_views` > 0 ORDER BY `count_views` DESC LIMIT 20') , 0);
} else {
    $sql = mysql_query("SELECT COUNT(*) AS `cnt`, AVG(`point`) AS `avg` FROM `cms_library_rating` GROUP BY `st_id` ORDER BY `avg` DESC, `cnt` DESC LIMIT 20");
    $total = mysql_num_rows($sql);
}

$page = $page >= ceil($total / $kmess) ? ceil($total / $kmess) : $page;
$start = $page == 1 ? 0 : ($page - 1) * $kmess;
if (!$total) {
  echo '<div>' . $lng['list_empty'] . '</div>';
}
else {
  if ($sort == 'read') {
    $sql = mysql_query('SELECT `id`, `name`, `time`, `author`, `count_views`, `cat_id`, `comments`, `count_comments`, `announce` FROM `library_texts` WHERE `count_views`>0 ORDER BY `count_views` DESC LIMIT ' . $start . ',' . $kmess);
  } else {
    $sql = mysql_query("SELECT `library_texts`.*, COUNT(*) AS `cnt`, AVG(`point`) AS `avg` FROM `cms_library_rating` JOIN `library_texts` ON `cms_library_rating`.`st_id` = `library_texts`.`id` GROUP BY `cms_library_rating`.`st_id` ORDER BY `avg` DESC, `cnt` DESC LIMIT " . $start . ',' . $kmess);      
  }
  
  $nav = ($total > $kmess) ? '<div class="topmenu">' . functions::display_pagination('?act=new&amp;', $start, $total, $kmess) . '</div>' : '';
  echo $nav;
  $i = 0;
  while ($row = mysql_fetch_assoc($sql)) {
    $rate = new Rating($row['id']);
    echo '<div class="list' . (++$i % 2 ? 2 : 1) . '">'
    . (file_exists('../files/library/images/small/' . $row['id'] . '.png')
    ? '<div class="avatar"><img src="../files/library/images/small/' . $row['id'] . '.png" alt="screen" /></div>'
    : '')
    . '<div class="righttable"><a href="?do=text&amp;id=' . $row['id'] . '">' . functions::checkout($row['name']) . '</a>'
    . ($adm ? ' <small><a href="?act=moder&amp;type=article&amp;id=' . $row['id'] . '">мод</a></small>' : '')
    . '<div>' . functions::checkout(bbcode::notags($row['announce'])) . '</div></div>'
    . '<div class="sub">' . $lng_lib['added'] . ': ' . functions::checkout($row['author']) . ' (' . functions::display_date($row['time']) . ')</div>'
    . $rate->view_rate()
    . '<div><span class="gray">' . $lng_lib['reads'] . ':</span> ' . $row['count_views'] . '</div>'
    . '<div>[<a href="?do=dir&amp;id=' . $row['cat_id'] . '">' . functions::checkout(mysql_result(mysql_query("SELECT `name` FROM `library_cats` WHERE `id`=" . $row['cat_id']) , 0)) . '</a>]</div>'
    . ($row['comments'] ? '<div><a href="?act=comments&amp;id=' . $row['id'] . '">' . $lng['comments'] . '</a> (' . intval($row['count_comments']) . ')</div>' : '')
    . '</div>';
  }
  echo '<div class="phdr">' . $lng['total'] . ': ' . intval($total) . '</div>';
  echo $nav;
}
echo '<div><a href="?">' . $lng_lib['to_library'] . '</a></div>' . PHP_EOL;