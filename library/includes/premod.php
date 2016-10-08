<?php

defined('_IN_JOHNCMS') or die('Error: restricted access');

echo '<div class="phdr"><strong><a href="?">' . _t('Library') . '</a></strong> | ' . _t('Moderation Articles') . '</div>';
if ($id && isset($_GET['yes'])) {
    $sql = "UPDATE `library_texts` SET `premod`=1 WHERE `id`=" . $id;
    echo '<div class="rmenu">' . _t('Article') . ' <strong>' . functions::checkout($db->query("SELECT `name` FROM `library_texts` WHERE `id`=" . $id)->fetchColumn()) . '</strong> ' . _t('Added to the database') . '</div>';
}
elseif (isset($_GET['all'])) {
    $sql = 'UPDATE `library_texts` SET `premod`=1';
    echo '<div>' . _t('All Articles added in database') . '</div>';
}
if (isset($_GET['yes']) || isset($_GET['all'])) {
    $db->exec($sql);
}
$total = $db->query('SELECT COUNT(*) FROM `library_texts` WHERE `premod`=0')->fetchColumn();
$page = $page >= ceil($total / $kmess) ? ceil($total / $kmess) : $page;
$start = $page == 1 ? 0 : ($page - 1) * $kmess;
if ($total) { 
    $stmt = $db->query('SELECT `id`, `name`, `time`, `uploader`, `uploader_id`, `cat_id` FROM `library_texts` WHERE `premod`=0 ORDER BY `time` DESC LIMIT ' . $start . ',' . $kmess);
    $i = 0;
    while ($row = $stmt->fetch()) {
        $dir_nav = new tree($row['cat_id']);
        $dir_nav->process_nav_panel();
        echo '<div class="list' . (++$i % 2 ? 2 : 1) . '">' 
        . (file_exists('../files/library/images/small/' . $row['id'] . '.png') 
        ? '<div class="avatar"><img src="../files/library/images/small/' . $row['id'] . '.png" alt="screen" /></div>' 
        : '') 
        . '<div class="righttable"><a href="index.php?id=' . $row['id'] . '">' . functions::checkout($row['name']) . '</a></div>'
        . '<div class="sub">' . _t('Who added') . ': ' . functions::checkout($row['uploader']) . ' (' . functions::display_date($row['time']) . ')</div>' 
        . '<div>' . $dir_nav->print_nav_panel() . '</div>' 
        . '<a href="?act=premod&amp;yes&amp;id=' . $row['id'] . '">' . _t('Approve') . '</a> | <a href="?act=del&amp;type=article&amp;id=' . $row['id'] . '">' . _t('Delete') . '</a>' 
        . '</div>';
    }
}
echo '<div class="phdr">' . _t('Total') . ': ' . intval($total) . '</div>';
echo ($total > $kmess) ? '<div class="topmenu">' . functions::display_pagination('?act=premod&amp;', $start, $total, $kmess) . '</div>' : '';
echo $total ? '<div><a href="?act=premod&amp;all">' . _t('Approve all') . '</a></div>' : '';
echo '<p><a href="?">' . _t('To Library') . '</a></p>' . PHP_EOL;