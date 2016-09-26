<?php

defined('_IN_JOHNCMS') or die('Error: restricted access');

/** @var PDO $db */
$db = App::getContainer()->get(PDO::class);
$lng = core::load_lng('dl');
$url = $set['homeurl'] . '/downloads/';

// Файлы юзера
$textl = $lng['user_files'];

//TODO: Переделать на класс Users
if (($user = Mobi::getUser()) === false || (!Mobi::$USER && !$user_id)) {
    echo $lng['user_does_not_exist'];
    exit;
}

if (!Mobi::$USER) {
    Mobi::$USER = $user_id;
}

echo '<div class="phdr"><a href="/profile?user=' . Mobi::$USER . '">' . $lng['profile'] . '</a></div>' .
    '<div class="user"><p>' . functions::displayUser($user, ['iphide' => 0]) . '</p></div>' .
    '<div class="phdr"><b>' . $lng['user_files'] . '</b></div>';
$total = $db->query("SELECT COUNT(*) FROM `download__files` WHERE `type` = '2'  AND `user_id` = " . Mobi::$USER)->fetchColumn();

// Навигация
if ($total > $kmess) {
    echo '<div class="topmenu">' . Functions::displayPagination($url . '?user=' . Mobi::$USER . '&amp;act=user_files&amp;', $start, $total, $kmess) . '</div>';
}

// Список файлов
$i = 0;

if ($total) {
    $req_down = $db->query("SELECT * FROM `download__files` WHERE `type` = '2'  AND `user_id` = " . Mobi::$USER . " ORDER BY `time` DESC " . $db->pagination());

    while ($res_down = $req_down->fetch()) {
        echo (($i++ % 2) ? '<div class="list2">' : '<div class="list1">') . Download::displayFile($res_down) . '</div>';
    }
} else {
    echo '<div class="rmenu"><p>' . $lng['list_empty'] . '</p></div>';
}

echo '<div class="phdr">' . $lng['total'] . ': ' . $total . '</div>';

// Навигация
if ($total > $kmess) {
    echo '<div class="topmenu">' . Functions::displayPagination($url . '?user=' . Mobi::$USER . '&amp;act=user_files&amp;', $start, $total, $kmess) . '</div>' .
        '<p><form action="' . $url . '" method="get">' .
        '<input type="hidden" name="USER" value="' . Mobi::$USER . '"/>' .
        '<input type="hidden" value="user_files" name="act" />' .
        '<input type="text" name="page" size="2"/><input type="submit" value="' . _t('To Page') . ' &gt;&gt;"/></form></p>';
}

echo '<p><a href="' . $url . '">' . _t('Downloads') . '</a></p>';
