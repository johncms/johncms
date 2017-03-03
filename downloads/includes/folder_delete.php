<?php
/*
 * JohnCMS NEXT Mobile Content Management System (http://johncms.com)
 *
 * For copyright and license information, please see the LICENSE.md
 * Installing the system or redistributions of files must retain the above copyright notice.
 *
 * @link        http://johncms.com JohnCMS Project
 * @copyright   Copyright (C) JohnCMS Community
 * @license     GPL-3
 */

defined('_IN_JOHNCMS') or die('Error: restricted access');

//TODO: Добавить проверку, пустой ли каталог, если нет, выводить предупреждение
//TODO: Добавить рекурсивное удаление

/** @var Psr\Container\ContainerInterface $container */
$container = App::getContainer();

/** @var PDO $db */
$db = $container->get(PDO::class);

/** @var Johncms\Api\UserInterface $systemUser */
$systemUser = $container->get(Johncms\Api\UserInterface::class);

// Удаление каталога
if ($systemUser->rights == 4 || $systemUser->rights >= 6) {
    $del_cat = $db->query("SELECT COUNT(*) FROM `download__category` WHERE `refid` = " . $id)->fetchColumn();
    $req = $db->query("SELECT * FROM `download__category` WHERE `id` = " . $id);

    if (!$req->rowCount() || $del_cat) {
        require_once('../system/head.php');
        echo ($del_cat ? _t('Before removing, delete subdirectories') : _t('The directory does not exist')) . ' <a href="?">' . _t('Downloads') . '</a>';
        require_once('../system/end.php');
        exit;
    }

    $res = $req->fetch();

    if (isset($_POST['delete'])) {
        $req_down = $db->query("SELECT * FROM `download__files` WHERE `refid` = " . $id);

        while ($res_down = $req_down->fetch()) {
            if (is_dir(DOWNLOADS_SCR . $res_down['id'])) {
                $dir_clean = opendir(DOWNLOADS_SCR . $res_down['id']);

                while ($file = readdir($dir_clean)) {
                    if ($file != '.' && $file != '..') {
                        @unlink(DOWNLOADS_SCR . $res_down['id'] . '/' . $file);
                    }
                }

                closedir($dir_clean);
                rmdir(DOWNLOADS_SCR . $res_down['id']);
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
        header('location: ?id=' . $res['refid']);
    } else {
        require_once('../system/head.php');
        echo '<div class="phdr"><b>' . _t('Delete Folder') . '</b></div>' .
            '<div class="rmenu"><p>' .
            _t('Do you really want to delete?') . '<br>' .
            '<form act="?act=folder_delete&amp;id=' . $id . '" method="post"><input type="submit" name="delete" value="' . _t('Delete') . '"></form>' .
            '</p></div>' .
            '<div class="phdr"><a href="?id=' . $id . '">' . _t('Back') . '</a></div>';
        require_once('../system/end.php');
    }
}
