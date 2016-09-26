<?php

defined('_IN_JOHNCMS') or die('Error: restricted access');

/** @var PDO $db */
$db = App::getContainer()->get(PDO::class);
$lng = core::load_lng('dl');
$url = $set['homeurl'] . '/downloads/';
$id = App::request()->getQuery('id', 0);

// Удаление файл
$req_down = $db->query("SELECT * FROM `download__files` WHERE `id` = '" . $id . "' AND (`type` = 2 OR `type` = 3)  LIMIT 1");
$res_down = $req_down->fetch();

if (!$req_down->rowCount() || !is_file($res_down['dir'] . '/' . $res_down['name'])) {
    echo $lng['not_found_file'] . ' <a href="' . $url . '">' . _t('Downloads') . '</a>';
    exit;
}

if ($rights == 4 || $rights >= 6) {
    if (isset($_GET['yes'])) {
        if (is_dir($screens_path . '/' . $id)) {
            $dir_clean = opendir($screens_path . '/' . $id);

            while ($file = readdir($dir_clean)) {
                if ($file != '.' && $file != '..') {
                    @unlink($screens_path . '/' . $id . '/' . $file);
                }
            }

            closedir($dir_clean);
            rmdir($screens_path . '/' . $id);
        }

        @unlink(ROOT_PATH . 'files/download/java_icons/' . $id . '.png');
        $req_file_more = $db->query("SELECT * FROM `download__more` WHERE `refid` = " . $id);

        if ($req_file_more->rowCount()) {
            while ($res_file_more = $req_file_more->fetch()) {
                if (is_file($res_down['dir'] . '/' . $res_file_more['name'])) {
                    @unlink($res_down['dir'] . '/' . $res_file_more['name']);
                }

                @unlink(ROOT_PATH . 'files/download/java_icons/' . $res_file_more['id'] . '_' . $id . '.png');
            }

            $db->exec("DELETE FROM `download__more` WHERE `refid` = " . $id);
        }

        $db->exec("DELETE FROM `download__bookmark` WHERE `file_id` = " . $id);
        $db->exec("DELETE FROM `download__comments` WHERE `sub_id` = " . $id);
        @unlink($res_down['dir'] . '/' . $res_down['name']);
        $dirid = $res_down['refid'];
        $sql = '';
        $i = 0;

        while ($dirid != '0' && $dirid != "") {
            $res = $db->query("SELECT `refid` FROM `download__category` WHERE `id` = '$dirid' LIMIT 1")->fetch();
            if ($i) {
                $sql .= ' OR ';
            }
            $sql .= '`id` = \'' . $dirid . '\'';
            $dirid = $res['refid'];
            ++$i;
        }

        $db->exec("UPDATE `download__category` SET `total` = (`total`-1) WHERE $sql");
        $db->exec("DELETE FROM `download__files` WHERE `id` = " . $id);
        $db->query("OPTIMIZE TABLE `download__files`");
        header('Location: ' . $url . '?id=' . $res_down['refid']);
    } else {
        echo '<div class="phdr"><b>' . $lng['delete_file'] . '</b></div>' .
            '<div class="rmenu"><p><a href="' . $url . '?act=delete_file&amp;id=' . $id . '&amp;yes"><b>' . $lng['delete'] . '</b></a></p></div>' .
            '<div class="phdr"><a href="' . $url . '?act=view&amp;id=' . $id . '">' . $lng['back'] . '</a></div>';
    }
} else {
    header('Location: ' . App::cfg()->sys->homeurl . '404');
}
