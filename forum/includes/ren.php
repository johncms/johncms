<?php

defined('_IN_JOHNCMS') or die('Error: restricted access');

if ($rights == 3 || $rights >= 6) {
    if (!$id) {
        require('../incfiles/head.php');
        echo functions::display_error(_t('Wrong data'));
        require('../incfiles/end.php');
        exit;
    }

    $ms = $db->query("SELECT * FROM `forum` WHERE `id` = '$id'")->fetch();

    if ($ms[type] != "t") {
        require('../incfiles/head.php');
        echo functions::display_error(_t('Wrong data'));
        require('../incfiles/end.php');
        exit;
    }

    if (isset($_POST['submit'])) {
        $nn = isset($_POST['nn']) ? trim($_POST['nn']) : '';

        if (!$nn) {
            require('../incfiles/head.php');
            echo functions::display_error($lng_forum['error_topic_name'], '<a href="index.php?act=ren&amp;id=' . $id . '">' . _t('Repeat') . '</a>');
            require('../incfiles/end.php');
            exit;
        }

        // Проверяем, есть ли тема с таким же названием?
        $pt = $db->query("SELECT * FROM `forum` WHERE `type` = 't' AND `refid` = '" . $ms['refid'] . "' and text=" . $db->quote($nn) . " LIMIT 1");

        if ($pt->rowCount()) {
            require('../incfiles/head.php');
            echo functions::display_error($lng_forum['error_topic_exists'], '<a href="index.php?act=ren&amp;id=' . $id . '">' . _t('Repeat') . '</a>');
            require('../incfiles/end.php');
            exit;
        }

        $db->exec("UPDATE `forum` SET  text=" . $db->quote($nn) . " WHERE id='" . $id . "'");
        header("Location: index.php?id=$id");
    } else {
        // Переименовываем тему
        require('../incfiles/head.php');
        echo '<div class="phdr"><a href="index.php?id=' . $id . '"><b>' . _t('Forum') . '</b></a> | ' . $lng_forum['topic_rename'] . '</div>' .
            '<div class="menu"><form action="index.php?act=ren&amp;id=' . $id . '" method="post">' .
            '<p><h3>' . $lng_forum['topic_name'] . '</h3>' .
            '<input type="text" name="nn" value="' . $ms['text'] . '"/></p>' .
            '<p><input type="submit" name="submit" value="' . _t('Save') . '"/></p>' .
            '</form></div>' .
            '<div class="phdr"><a href="index.php?id=' . $id . '">' . _t('Back') . '</a></div>';
    }
} else {
    require('../incfiles/head.php');
    echo functions::display_error($lng['access_forbidden']);
}
