<?php

use Library\Hashtags;
use Library\Rating;

$total = $db->query('SELECT COUNT(*) FROM `library_texts` WHERE `premod`=1 AND `cat_id`=' . $id)->fetchColumn();
$page = $page >= ceil($total / $user->config->kmess) ? ceil($total / $user->config->kmess) : $page;
$start = $page === 1 ? 0 : ($page - 1) * $user->config->kmess;
$nav = ($total > $user->config->kmess) ? '<div class="topmenu">' . $tools->displayPagination('?do=dir&amp;id=' . $id . '&amp;', $start, $total, $user->config->kmess) . '</div>' : '';

if ($total) {
    $sql2 = $db->query(
        'SELECT `id`, `name`, `time`, `uploader`, `uploader_id`, `count_views`, `comm_count`, `comments`, `announce`
                            FROM `library_texts`
                            WHERE `premod`=1 AND `cat_id`=' . $id . '
                            ORDER BY `id` DESC LIMIT ' . $start . ',' . $user->config->kmess
    );
    echo $nav;

    while ($row = $sql2->fetch()) {
        echo '<div class="list' . ((++$i % 2) ? 2 : 1) . '">'
            . (file_exists(UPLOAD_PATH . 'library/images/small/' . $row['id'] . '.png')
                ? '<div class="avatar"><img src="../upload/library/images/small/' . $row['id'] . '.png" alt="screen" /></div>'
                : '')
            . '<div class="righttable"><h4><a href="?id=' . $row['id'] . '">' . $tools->checkout($row['name']) . '</a></h4>'
            . '<div><small>' . $tools->checkout($row['announce'], 0, 0) . '</small></div></div>';

        // Описание к статье
        $obj = new Hashtags($row['id']);
        $rate = new Rating($row['id']);
        $uploader = $row['uploader_id']
            ? '<a href="' . $config['homeurl'] . '/profile/?user=' . $row['uploader_id'] . '">' . $tools->checkout($row['uploader']) . '</a>'
            : $tools->checkout($row['uploader']);
        echo '<table class="desc">'
            // Тэги
            . ($obj->getAllStatTags()
                ? '<tr><td class="caption">' . _t('The Tags') . ':</td>'
                . '<td>' . $obj->getAllStatTags(1) . '</td></tr>'
                : '')
            // Кто добавил?
            . '<tr>'
            . '<td class="caption">' . _t('Who added') . ':</td>'
            . '<td>' . $uploader . ' (' . $tools->displayDate($row['time']) . ')</td>'
            . '</tr>'
            // Рейтинг
            . '<tr>'
            . '<td class="caption">' . _t('Rating') . ':</td>'
            . '<td>' . $rate->viewRate() . '</td>'
            . '</tr>';
        echo '</table></div>';
    }
} else {
    echo '<div class="menu">' . _t('The list is empty') . '</div>';
}

echo '<div class="phdr">' . _t('Total') . ': ' . $total . '</div>';
echo $nav;

if ((isset($id) && $user->isValid()) && ($adm || ($db->query('SELECT `user_add` FROM `library_cats` WHERE `id`=' . $id)->fetchColumn() > 0))) {
    echo '<p><a href="?act=addnew&amp;id=' . $id . '">' . _t('Write Article') . '</a>'
        . ($adm ? ('<br><a href="?act=moder&amp;type=dir&amp;id=' . $id . '">' . _t('Edit') . '</a><br>'
            . '<a href="?act=del&amp;type=dir&amp;id=' . $id . '">' . _t('Delete') . '</a>') : '')
        . '</p>';
}
