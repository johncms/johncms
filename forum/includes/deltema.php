<?php

defined('_IN_JOHNCMS') or die('Error: restricted access');

if ($rights == 3 || $rights >= 6) {
    if (!$id) {
        require('../incfiles/head.php');
        echo functions::display_error($lng['error_wrong_data']);
        require('../incfiles/end.php');
        exit;
    }

    /** @var PDO $db */
    $db = App::getContainer()->get(PDO::class);

    // Проверяем, существует ли тема
    $req = $db->query("SELECT * FROM `forum` WHERE `id` = '$id' AND `type` = 't'");

    if (!$req->rowCount()) {
        require('../incfiles/head.php');
        echo functions::display_error($lng_forum['error_topic_deleted']);
        require('../incfiles/end.php');
        exit;
    }

    $res = $req->fetch();

    if (isset($_POST['submit'])) {
        $del = isset($_POST['del']) ? intval($_POST['del']) : null;

        if ($del == 2 && core::$user_rights == 9) {
            // Удаляем топик
            $req1 = $db->query("SELECT * FROM `cms_forum_files` WHERE `topic` = '$id'");

            if ($req1->rowCount()) {
                while ($res1 = $req1->fetch()) {
                    unlink('../files/forum/attach/' . $res1['filename']);
                }

                $db->exec("DELETE FROM `cms_forum_files` WHERE `topic` = '$id'");
                $db->query("OPTIMIZE TABLE `cms_forum_files`");
            }

            $db->exec("DELETE FROM `forum` WHERE `refid` = '$id'");
            $db->exec("DELETE FROM `forum` WHERE `id`='$id'");
        } elseif ($del = 1) {
            // Скрываем топик
            $db->exec("UPDATE `forum` SET `close` = '1', `close_who` = '$login' WHERE `id` = '$id'");
            $db->exec("UPDATE `cms_forum_files` SET `del` = '1' WHERE `topic` = '$id'");
        }
        header('Location: index.php?id=' . $res['refid']);
    } else {
        // Меню выбора режима удаления темы
        require('../incfiles/head.php');
        echo '<div class="phdr"><a href="index.php?id=' . $id . '"><b>' . _t('Forum') . '</b></a> | ' . $lng_forum['topic_delete'] . '</div>' .
            '<div class="rmenu"><form method="post" action="index.php?act=deltema&amp;id=' . $id . '">' .
            '<p><h3>' . $lng['delete_confirmation'] . '</h3>' .
            '<input type="radio" value="1" name="del" checked="checked"/>&#160;' . $lng['hide'] . '<br />' .
            (core::$user_rights == 9 ? '<input type="radio" value="2" name="del" />&#160;' . $lng['delete'] . '</p>' : '') .
            '<p><input type="submit" name="submit" value="' . $lng['do'] . '" /></p>' .
            '<p><a href="index.php?id=' . $id . '">' . $lng['cancel'] . '</a>' .
            '</p></form></div>' .
            '<div class="phdr">&#160;</div>';
    }
} else {
    echo functions::display_error($lng['access_forbidden']);
}
