<?php

/**
 * This file is part of JohnCMS Content Management System.
 *
 * @copyright JohnCMS Community
 * @license   https://opensource.org/licenses/GPL-3.0 GPL-3.0
 * @link      https://johncms.com JohnCMS Project
 */

declare(strict_types=1);

defined('_IN_JOHNCMS') || die('Error: restricted access');

/**
 * @var PDO $db
 * @var Johncms\System\Users\User $user
 */

// Удаление каталога
$nav_chain->add(__('Delete Folder'));
$del_cat = $db->query('SELECT COUNT(*) FROM `download__category` WHERE `refid` = ' . $id)->fetchColumn();
$req = $db->query('SELECT * FROM `download__category` WHERE `id` = ' . $id);

if ($del_cat || ! $req->rowCount()) {
    echo $view->render(
        'system::pages/result',
        [
            'title'         => __('Delete Folder'),
            'type'          => 'alert-danger',
            'message'       => $del_cat ? __('Before removing, delete subdirectories') : __('The directory does not exist'),
            'back_url'      => $urls['downloads'],
            'back_url_name' => __('Downloads'),
        ]
    );
    exit;
}

$res = $req->fetch();

if (isset($_POST['delete'])) {
    $req_down = $db->query('SELECT * FROM `download__files` WHERE `refid` = ' . $id);

    while ($res_down = $req_down->fetch()) {
        if (is_dir(DOWNLOADS_SCR . $res_down['id'])) {
            $dir_clean = opendir(DOWNLOADS_SCR . $res_down['id']);

            while ($file = readdir($dir_clean)) {
                if ($file !== '.' && $file !== '..') {
                    @unlink(DOWNLOADS_SCR . $res_down['id'] . '/' . $file);
                }
            }

            closedir($dir_clean);
            rmdir(DOWNLOADS_SCR . $res_down['id']);
        }

        $req_file_more = $db->query('SELECT * FROM `download__more` WHERE `refid` = ' . $res_down['id']);

        @unlink($res_down['dir'] . '/' . $res_down['name']);
        $db->exec('DELETE FROM `download__more` WHERE `refid` = ' . $res_down['id']);
        $db->exec('DELETE FROM `download__comments` WHERE `sub_id` = ' . $res_down['id']);
        $db->exec('DELETE FROM `download__bookmark` WHERE `file_id` = ' . $res_down['id']);
    }

    $db->exec('DELETE FROM `download__files` WHERE `refid` = ' . $id);
    $db->exec('DELETE FROM `download__category` WHERE `id` = ' . $id);
    $db->query('OPTIMIZE TABLE `download__bookmark`, `download__files`, `download__comments`, `download__more`, `download__category`');

    rmdir($res['dir']);
    header('location: ?id=' . $res['refid']);
} else {
    echo $view->render(
        'downloads::folder_delete',
        [
            'title'      => __('Delete Folder'),
            'page_title' => __('Delete Folder'),
            'id'         => $id,
            'urls'       => $urls,
            'action_url' => '?act=folder_delete&amp;id=' . $id,
            'back_url'   => '?id=' . $id,
        ]
    );
}
