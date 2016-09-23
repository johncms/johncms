<?php

defined('_IN_JOHNCMS') or die('Error: restricted access');

/** @var PDO $db */
$db = App::getContainer()->get(PDO::class);
$lng = core::load_lng('dl');
$url = $set['homeurl'] . '/downloads/';

// Закладки
$textl = $lng['download_bookmark'];
require '../incfiles/head.php';

if (!$user_id) {
    echo $lng['access_guest_forbidden'];
    exit;
}

echo '<div class="phdr"><b>' . $textl . '</b></div>';
$total = $db->query("SELECT COUNT(*) FROM `download__bookmark` WHERE `user_id` = " . $user_id)->fetchColumn();

// Навигация
if ($total > $kmess) {
    echo '<div class="topmenu">' . Functions::displayPagination($url . '?act=bookmark&amp;', $start, $total, $kmess) . '</div>';
}

// Список закладок
if ($total) {
    $req_down = $db->query("SELECT `download__files`.*, `download__bookmark`.`id` AS `bid`
    FROM `download__files` LEFT JOIN `download__bookmark` ON `download__files`.`id` = `download__bookmark`.`file_id`
    WHERE `download__bookmark`.`user_id`=" . $user_id . " ORDER BY `download__files`.`time` DESC " . $db->pagination());
    $i = 0;

    while ($res_down = $req_down->fetch()) {
        echo (($i++ % 2) ? '<div class="list2">' : '<div class="list1">') . Download::displayFile($res_down) . '</div>';
    }
} else {
    echo '<div class="menu"><p>' . _t('The list is empty') . '</p></div>';
}

echo '<div class="phdr">' . _t('Total') . ': ' . $total . '</div>';

// Навигация
if ($total > $kmess) {
    echo '<div class="topmenu">' . Functions::displayPagination($url . '?act=bookmark&amp;', $start, $total, $kmess) . '</div>' .
        '<p><form action="' . $url . '" method="get">' .
        '<input type="hidden" value="bookmark" name="act" />' .
        '<input type="text" name="page" size="2"/><input type="submit" value="' . $lng['to_page'] . ' &gt;&gt;"/></form></p>';
}

echo '<p><a href="' . $url . '">' . $lng['download_title'] . '</a></p>';
require '../incfiles/end.php';
