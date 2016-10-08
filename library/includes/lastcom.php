<?php

defined('_IN_JOHNCMS') or die('Error: restricted access');

echo '<div class="phdr"><strong><a href="?">' . _t('Library') . '</a></strong> | ' . _t('Latest comments') . '</div>';



$stmt = $db->query('SELECT `cms_library_comments`.`user_id` , `cms_library_comments`.`text` , `library_texts`.`name` , `library_texts`.`comm_count` , `library_texts`.`id` , `cms_library_comments`.`time` 
FROM `cms_library_comments` 
JOIN `library_texts` ON `cms_library_comments`.`sub_id` = `library_texts`.`id` 
GROUP BY `library_texts`.`id` 
ORDER BY `cms_library_comments`.`time` DESC 
LIMIT 20');

if ($stmt->rowCount()) {    
    
$i = 0;
  while ($row = $stmt->fetch()) {
    echo '<div class="list' . (++$i % 2 ? 2 : 1) . '">'
    . (file_exists('../files/library/images/small/' . $row['id'] . '.png')
    ? '<div class="avatar"><img src="../files/library/images/small/' . $row['id'] . '.png" alt="screen" /></div>' 
    : '')
    . '<div class="righttable"><a href="?act=comments&amp;id=' . $row['id'] . '">' . functions::checkout($row['name']) . '</a>'
    . '<div>' . functions::checkout(substr(bbcode::notags($row['text']), 0, 500)) . '</div></div>'
    . '<div class="sub">' . _t('Who added') . ': ' . functions::checkout($db->query("SELECT `name` FROM `users` WHERE `id` = " . $row['user_id'])->fetchColumn()) . ' (' . functions::display_date($row['time']) . ')</div>'
    . '</div>';
  }
} else {
    echo '<div class="menu"><p>' . _t('The list is empty') . '</p></div>';
}

echo '<div class="phdr"><a href="?">' . _t('Back') . '</a></div>' . PHP_EOL;