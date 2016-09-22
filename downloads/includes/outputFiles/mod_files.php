<?php

defined('_IN_JOHNCMS') or die('Error: restricted access');

$lng = core::load_lng('dl');
$url = $set['homeurl'] . '/downloads/';

// Файлы на модерации
$textl = $lng['mod_files'];

if ($rights == 4 || $rights >= 6) {
    /** @var PDO $db */
    $db = App::getContainer()->get(PDO::class);

    echo '<div class="phdr"><a href="?"><b>' . $lng['downloads'] . '</b></a> | ' . $textl . '</div>';

    if ($id) {
        $db->exec("UPDATE `download__files` SET `type` = 2 WHERE `id` = '" . $id . "' LIMIT 1");
        echo '<div class="gmenu">' . $lng['file_accepted_ok'] . '</div>';
    } else {
        if (isset($_POST['all_mod'])) {
            $db->exec("UPDATE `download__files` SET `type` = 2 WHERE `type` = '3'");
            echo '<div class="gmenu">' . $lng['file_accepted_all_ok'] . '</div>';
        }
    }

    $total = $db->query("SELECT COUNT(*) FROM `download__files` WHERE `type` = '3'")->fetchColumn();

    // Навигация
    if ($total > $kmess) {
        echo '<div class="topmenu">' . Functions::displayPagination($url . '?act=mod_files&amp;', $start, $total, $kmess) . '</div>';
    }

    $i = 0;

    if ($total) {
        $req_down = $db->query("SELECT * FROM `download__files` WHERE `type` = '3' ORDER BY `time` DESC " . $db->pagination());
        while ($res_down = $req_down->fetch()) {
            echo (($i++ % 2) ? '<div class="list2">' : '<div class="list1">') . Download::displayFile($res_down) .
                '<div class="sub"><a href="' . $url . '?act=mod_files&amp;id=' . $res_down['id'] . '">' . $lng['file_accepted'] . '</a> | ' .
                '<span class="red"><a href="' . $url . '?act=delete_file&amp;id=' . $res_down['id'] . '">' . $lng['delete'] . '</a></span></div></div>';
        }

        echo '<div class="rmenu"><form name="" action="' . $url . '?act=mod_files" method="post"><input type="submit" name="all_mod" value="' . $lng['file_accepted_all'] . '"/></form></div>';
    } else {
        echo '<div class="menu"><p>' . $lng['list_empty'] . '</p></div>';
    }

    echo '<div class="phdr">' . $lng['total'] . ': ' . $total . '</div>';

    // Навигация
    if ($total > $kmess) {
        echo '<div class="topmenu">' . Functions::displayPagination($url . '?act=mod_files&amp;', $start, $total, $kmess) . '</div>' .
            '<p><form action="' . $url . '" method="get">' .
            '<input type="hidden" value="top_users" name="act" />' .
            '<input type="text" name="page" size="2"/><input type="submit" value="' . $lng['to_page'] . ' &gt;&gt;"/></form></p>';
    }

    echo '<p><a href="' . $url . '">' . $lng['download_title'] . '</a></p>';
} else {
    header('Location: ' . App::cfg()->sys->homeurl . '404');
}
