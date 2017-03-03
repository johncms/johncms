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

/** @var Psr\Container\ContainerInterface $container */
$container = App::getContainer();

/** @var PDO $db */
$db = $container->get(PDO::class);

/** @var Johncms\Api\UserInterface $systemUser */
$systemUser = $container->get(Johncms\Api\UserInterface::class);

require '../system/head.php';

// Удаление файл
$req_down = $db->query("SELECT * FROM `download__files` WHERE `id` = '" . $id . "' AND (`type` = 2 OR `type` = 3)  LIMIT 1");
$res_down = $req_down->fetch();

if (!$req_down->rowCount() || !is_file($res_down['dir'] . '/' . $res_down['name'])) {
    echo _t('File not found') . ' <a href="?">' . _t('Downloads') . '</a>';
    require '../system/end.php';
    exit;
}

if ($systemUser->rights == 4 || $systemUser->rights >= 6) {
    if (isset($_GET['yes'])) {
        if (is_dir(DOWNLOADS_SCR . $id)) {
            $dir_clean = opendir(DOWNLOADS_SCR . $id);

            while ($file = readdir($dir_clean)) {
                if ($file != '.' && $file != '..') {
                    @unlink(DOWNLOADS_SCR . $id . '/' . $file);
                }
            }

            closedir($dir_clean);
            rmdir(DOWNLOADS_SCR . $id);
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
        header('Location: ?id=' . $res_down['refid']);
    } else {
        echo '<div class="phdr"><b>' . _t('Delete File') . '</b></div>' .
            '<div class="rmenu"><p><a href="?act=delete_file&amp;id=' . $id . '&amp;yes"><b>' . _t('Delete') . '</b></a></p></div>' .
            '<div class="phdr"><a href="?act=view&amp;id=' . $id . '">' . _t('Back') . '</a></div>';
    }
}

require '../system/end.php';
