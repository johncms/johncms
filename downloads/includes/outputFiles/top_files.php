<?php

defined('_IN_JOHNCMS') or die('Error: restricted access');

/** @var PDO $db */
$db = App::getContainer()->get(PDO::class);
$lng = core::load_lng('dl');
$url = $set['homeurl'] . '/downloads/';

// Топ файлов
if ($id == 2) {
    $textl = $lng['top_files_comments'];
} elseif ($id == 1) {
    $textl = $lng['top_files_download'];
} else {
    $textl = $lng['top_files_popular'];
}

//TODO: Переделать на получение настроек из таблицы модулей
$linkTopComments = App::cfg()->sys->acl_downloads_comm || $rights >= 7 ? '<br><a href="' . $url . '?act=top_files&amp;id=2">' . $lng['top_files_comments'] . '</a>' : '';
echo '<div class="phdr"><a href="?"><b>' . $lng['downloads'] . '</b></a> | ' . $textl . ' (' . $set_down['top'] . ')</div>';

//TODO: Переделать на получение настроек из таблицы модулей
if ($id == 2 && (App::cfg()->sys->acl_downloads_comm || $rights >= 7)) {
    echo '<div class="gmenu"><a href="' . $url . '?act=top_files&amp;id=0">' . $lng['top_files_popular'] . '</a><br>' .
        '<a href="' . $url . '?act=top_files&amp;id=1">' . $lng['top_files_download'] . '</a></div>';
    $sql = '`total`';
} elseif ($id == 1) {
    echo '<div class="gmenu"><a href="' . $url . '?act=top_files&amp;id=0">' . $lng['top_files_popular'] . '</a>' . $linkTopComments . '</div>';
    $sql = '`field`';
} else {
    echo '<div class="gmenu"><a href="' . $url . '?act=top_files&amp;id=1">' . $lng['top_files_download'] . '</a>' . $linkTopComments . '</div>';
    $sql = '`rate`';
}

// Выводим список
$req_down = $db->query("SELECT * FROM `download__files` WHERE `type` = 2 ORDER BY $sql DESC LIMIT " . $set_down['top']);
$i = 0;

while ($res_down = $req_down->fetch()) {
    echo (($i++ % 2) ? '<div class="list2">' : '<div class="list1">') . Download::displayFile($res_down, 1) . '</div>';
}

echo '<div class="phdr"><a href="' . $url . '">' . _t('Downloads') . '</a></div>';
