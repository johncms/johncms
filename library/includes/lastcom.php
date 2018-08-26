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

echo '<div class="phdr"><strong><a href="?">' . $lng['library'] . '</a></strong> | ' . $lng_lib['last_comments'] . '</div>';

if ($db->query('SELECT COUNT(*) FROM `cms_library_comments`')->fetchColumn() > 0) {

$stmt = $db->query('SELECT `cms_library_comments`.`user_id` , `cms_library_comments`.`text` , `library_texts`.`name` , `library_texts`.`count_comments` , `library_texts`.`id` , `cms_library_comments`.`time` 
FROM `cms_library_comments` 
JOIN `library_texts` ON `cms_library_comments`.`sub_id` = `library_texts`.`id` 
GROUP BY `library_texts`.`id` 
ORDER BY `cms_library_comments`.`time` DESC 
LIMIT 20');    
    
$i = 0;
  while ($row = $stmt->fetch()) {
    echo '<div class="list' . (++$i % 2 ? 2 : 1) . '">'
    . (file_exists('../files/library/images/small/' . $row['id'] . '.png')
    ? '<div class="avatar"><img src="../files/library/images/small/' . $row['id'] . '.png" alt="screen" /></div>' 
    : '')
    . '<div class="righttable"><a href="?act=comments&amp;id=' . $row['id'] . '">' . functions::checkout($row['name']) . '</a>'
    . '<div>' . functions::checkout(substr(bbcode::notags($row['text']), 0, 500)) . '</div></div>'
    . '<div class="sub">' . $lng_lib['added'] . ': ' . functions::checkout($db->query("SELECT `name` FROM `users` WHERE `id` = " . $row['user_id'])->fetchColumn()) . ' (' . functions::display_date($row['time']) . ')</div>'
    . '</div>';
  }
} else {
    echo '<div class="menu"><p>' . $lng['list_empty'] . '</p></div>';
}

echo '<div class="phdr"><a href="?">' . $lng['back'] . '</a></div>' . PHP_EOL;