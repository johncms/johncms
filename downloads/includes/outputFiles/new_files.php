<?php

defined('_IN_JOHNCMS') or die('Error: restricted access');

/** @var PDO $db */
$db = App::getContainer()->get(PDO::class);
$lng = core::load_lng('dl');
$url = $set['homeurl'] . '/downloads/';

// Новые файлы
$textl = $lng['new_files'];
$sql_down = '';

if ($id) {
    $cat = $db->query("SELECT * FROM `download__category` WHERE `id` = '" . $id . "' LIMIT 1");
    $res_down_cat = $cat->fetch();

    if (!$cat->rowCount() || !is_dir($res_down_cat['dir'])) {
        echo $lng['not_found_dir'] . '<a href="' . $url . '">' . $lng['download_title'] . '</a>';
        exit;
    }

    $title_pages = htmlspecialchars(mb_substr($res_down_cat['rus_name'], 0, 30));
    $textl = $lng['new_files'] . ': ' . (mb_strlen($res_down_cat['rus_name']) > 30 ? $title_pages . '...' : $title_pages);
    $sql_down = ' AND `dir` LIKE \'' . ($res_down_cat['dir']) . '%\' ';
}

echo '<div class="phdr"><b>' . $textl . '</b></div>';
$total = $db->query("SELECT COUNT(*) FROM `download__files` WHERE `type` = '2'  AND `time` > $sql_down")->fetchColumn();

// Навигация
if ($total > $kmess) {
    echo '<div class="topmenu">' . Functions::displayPagination($url . '?id=' . $id . '&amp;act=new_files&amp;', $start, $total, $kmess) . '</div>';
}

// Выводим список
if ($total) {
    $i = 0;
    $req_down = $db->query("SELECT * FROM `download__files` WHERE `type` = '2'  AND `time` > $old $sql_down ORDER BY `time` DESC " . $db->pagination());

    while ($res_down = $req_down->fetch()) {
        echo (($i++ % 2) ? '<div class="list2">' : '<div class="list1">') . Download::displayFile($res_down) . '</div>';
    }
} else {
    echo '<div class="rmenu"><p>' . $lng['list_empty'] . '</p></div>';
}

echo '<div class="phdr">' . $lng['total'] . ': ' . $total . '</div>';

// Навигация
if ($total > $kmess) {
    echo '<div class="topmenu">' . Functions::displayPagination($url . '?id=' . $id . '&amp;act=new_files&amp;', $start, $total, $kmess) . '</div>' .
        '<p><form action="' . $url . '" method="get">' .
        '<input type="hidden" name="id" value="' . $id . '"/>' .
        '<input type="hidden" value="new_files" name="act" />' .
        '<input type="text" name="page" size="2"/><input type="submit" value="' . $lng['to_page'] . ' &gt;&gt;"/></form></p>';
}

echo '<p><a href="' . $url . '?id=' . $id . '">' . $lng['download_title'] . '</a></p>';
