<?php

declare(strict_types=1);

/*
 * This file is part of JohnCMS Content Management System.
 *
 * @copyright JohnCMS Community
 * @license   https://opensource.org/licenses/GPL-3.0 GPL-3.0
 * @link      https://johncms.com JohnCMS Project
 */

defined('_IN_JOHNCMS') || die('Error: restricted access');

/**
 * @var PDO                       $db
 * @var Johncms\System\Users\User $user
 */

$req_down = $db->query("SELECT * FROM `download__files` WHERE `id` = '" . $id . "' AND (`type` = 2 OR `type` = 3)  LIMIT 1");
$res_down = $req_down->fetch();

if (! $req_down->rowCount() || ! is_file($res_down['dir'] . '/' . $res_down['name'])) {
    echo _t('File not found') . ' <a href="?">' . _t('Downloads') . '</a>';
    echo $view->render('system::app/old_content', ['title' => $textl ?? '', 'content' => ob_get_clean()]);
    exit;
}

if ($user->rights == 4 || $user->rights >= 6) {
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

        @unlink(UPLOAD_PATH . 'download/java_icons/' . $id . '.png');
        $req_file_more = $db->query('SELECT * FROM `download__more` WHERE `refid` = ' . $id);

        if ($req_file_more->rowCount()) {
            while ($res_file_more = $req_file_more->fetch()) {
                if (is_file($res_down['dir'] . '/' . $res_file_more['name'])) {
                    @unlink($res_down['dir'] . '/' . $res_file_more['name']);
                }
            }

            $db->exec('DELETE FROM `download__more` WHERE `refid` = ' . $id);
        }

        $db->exec('DELETE FROM `download__bookmark` WHERE `file_id` = ' . $id);
        $db->exec('DELETE FROM `download__comments` WHERE `sub_id` = ' . $id);
        @unlink($res_down['dir'] . '/' . $res_down['name']);
        $dirid = $res_down['refid'];
        $sql = '';
        $i = 0;

        while ($dirid != '0' && $dirid != '') {
            $res = $db->query("SELECT `refid` FROM `download__category` WHERE `id` = '${dirid}' LIMIT 1")->fetch();
            if ($i) {
                $sql .= ' OR ';
            }
            $sql .= '`id` = \'' . $dirid . '\'';
            $dirid = $res['refid'];
            ++$i;
        }

        $db->exec("UPDATE `download__category` SET `total` = (`total`-1) WHERE ${sql}");
        $db->exec('DELETE FROM `download__files` WHERE `id` = ' . $id);
        $db->query('OPTIMIZE TABLE `download__files`');
        header('Location: ?id=' . $res_down['refid']);
    } else {
        echo '<div class="phdr"><b>' . _t('Delete File') . '</b></div>' .
            '<div class="rmenu"><p><a href="?act=delete_file&amp;id=' . $id . '&amp;yes"><b>' . _t('Delete') . '</b></a></p></div>' .
            '<div class="phdr"><a href="?act=view&amp;id=' . $id . '">' . _t('Back') . '</a></div>';
    }
}

echo $view->render('system::app/old_content', ['title' => $textl ?? '', 'content' => ob_get_clean()]);
