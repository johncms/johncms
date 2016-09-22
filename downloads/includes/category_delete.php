<?php

defined('_IN_JOHNCMS') or die('Error: restricted access');

//TODO: Добавить проверку, пустой ли каталог, если нет, выводить предупреждение
//TODO: Добавить рекурсивное удаление
//TODO: Переделать на POST запрос с валидацией по токену
$lng = core::load_lng('dl');
$url = $set['homeurl'] . '/downloads/';
$id = isset($_REQUEST['id']) ? abs(intval($_REQUEST['id'])) : 0;

// Удаление каталога
if ($rights == 4 || $rights >= 6) {
    /** @var PDO $db */
    $db = App::getContainer()->get(PDO::class);

    $del_cat = $db->query("SELECT COUNT(*) FROM `download__category` WHERE `refid` = " . $id)->fetchColumn();
    $req = $db->query("SELECT * FROM `download__category` WHERE `id` = " . $id);

    if (!$req->rowCount() || $del_cat) {
        echo ($del_cat ? $lng['sub_catalogs'] : $lng['not_found_dir']) . ' <a href="' . $url . '">' . $lng['download_title'] . '</a>';
        exit;
    }

    $res = $req->fetch();

    if (isset($_GET['yes'])) {
        $req_down = $db->query("SELECT * FROM `download__files` WHERE `refid` = " . $id);

        while ($res_down = $req_down->fetch()) {
            if (is_dir($screens_path . '/' . $res_down['id'])) {
                $dir_clean = opendir($screens_path . '/' . $res_down['id']);

                while ($file = readdir($dir_clean)) {
                    if ($file != '.' && $file != '..') {
                        @unlink($screens_path . '/' . $res_down['id'] . '/' . $file);
                    }
                }

                closedir($dir_clean);
                rmdir($screens_path . '/' . $res_down['id']);
            }

            @unlink(ROOT_PATH . 'files/download/java_icons/' . $res_down['id'] . '.png');
            $req_file_more = $db->query("SELECT * FROM `download__more` WHERE `refid` = " . $res_down['id']);

            while ($res_file_more = $req_file_more->fetch()) {
                @unlink($res_down['dir'] . '/' . $res_file_more['name']);
                @unlink(ROOT_PATH . 'files/download/java_icons/' . $res_file_more['id'] . '.png');
            }

            @unlink($res_down['dir'] . '/' . $res_down['name']);
            $db->exec("DELETE FROM `download__more` WHERE `refid` = " . $res_down['id']);
            $db->exec("DELETE FROM `download__comments` WHERE `sub_id` = " . $res_down['id']);
            $db->exec("DELETE FROM `download__bookmark` WHERE `file_id` = " . $res_down['id']);
        }

        $db->exec("DELETE FROM `download__files` WHERE `refid` = " . $id);
        $db->exec("DELETE FROM `download__category` WHERE `id` = " . $id);
        $db->query("OPTIMIZE TABLE `download__bookmark`, `download__files`, `download__comments`, `download__more`, `download__category`");

        rmdir($res['dir']);
        header('location: ' . $url . '?id=' . $res['refid']);
    } else {
        require_once('../incfiles/head.php');
        echo '<div class="phdr"><b>' . $lng['download_del_cat'] . '</b></div>' .
            '<div class="rmenu"><p><a href="?act=delete_cat&amp;id=' . $id . '&amp;yes"><b>' . _t('Delete') . '</b></a></p></div>' .
            '<div class="phdr"><a href="?id=' . $id . '">' . _t('Back') . '</a></div>';
        require_once('../incfiles/end.php');
    }
} else {
    header('Location: ' . App::cfg()->sys->homeurl . '404');
}
