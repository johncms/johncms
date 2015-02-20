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
echo '<div class="phdr"><b>' . $lng_lib['new_articles'] . '</b></div>';
$total = mysql_result(mysql_query("SELECT COUNT(*) FROM `library_texts` WHERE `time` > '" . (time() - 259200) . "' AND `premod`=1") , 0);
$page = $page >= ceil($total / $kmess) ? ceil($total / $kmess) : $page;
$start = $page == 1 ? 0 : ($page - 1) * $kmess;
$sql = mysql_query("SELECT `id`, `name`, `time`, `author`, `cat_id`, `announce` FROM `library_texts` WHERE `time` > '" . (time() - 259200) . "' AND `premod`=1 ORDER BY `time` DESC LIMIT " . $start . "," . $kmess);
$nav = ($total > $kmess) ? '<div class="topmenu">' . functions::display_pagination('?act=new&amp;', $start, $total, $kmess) . '</div>' : '';
echo $nav;
if ($total) {
  $i = 0;
  while ($row = mysql_fetch_assoc($sql)) {
    echo '<div class="list' . (++$i % 2 ? 2 : 1) . '">'
    . (file_exists('../files/library/images/small/' . $row['id'] . '.png')
    ? '<div class="avatar"><img src="../files/library/images/small/' . $row['id'] . '.png" alt="screen" /></div>' 
    : '')
    . '<div class="righttable"><a href="?do=text&amp;id=' . $row['id'] . '">' . $row['name'] . '</a>'
    . '<div>' . bbcode::notags($row['announce']) . '</div></div>'
    . '<div class="sub">' . $lng_lib['added'] . ': ' . $row['author'] . ' (' . functions::display_date($row['time']) . ')</div>'
    . '<div>[<a href="?do=dir&amp;id=' . $row['cat_id'] . '">' . mysql_result(mysql_query("SELECT `name` FROM `library_cats` WHERE `id`=" . $row['cat_id']) , 0) . '</a>]</div>'
    . '</div>';
  }
}
echo '<div class="phdr">' . $lng['total'] . ': ' . intval($total) . '</div>';
echo $nav;
echo '<div><a href="?">' . $lng_lib['to_library'] . '</a></div>' . PHP_EOL;