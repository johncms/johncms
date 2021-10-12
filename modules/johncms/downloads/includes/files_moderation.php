<?php

/**
 * This file is part of JohnCMS Content Management System.
 *
 * @copyright JohnCMS Community
 * @license   https://opensource.org/licenses/GPL-3.0 GPL-3.0
 * @link      https://johncms.com JohnCMS Project
 */

declare(strict_types=1);

use Downloads\Download;

defined('_IN_JOHNCMS') || die('Error: restricted access');

/**
 * @var PDO $db
 * @var Johncms\System\Legacy\Tools $tools
 * @var Johncms\System\Users\User $user
 */

$title = __('Files on moderation');

if ($id) {
    $db->exec("UPDATE `download__files` SET `type` = 2 WHERE `id` = '" . $id . "' LIMIT 1");
    echo $view->render(
        'system::pages/result',
        [
            'title'         => $title,
            'type'          => 'alert-success',
            'message'       => __('File accepted'),
            'back_url'      => $urls['downloads'],
            'back_url_name' => __('Downloads'),
        ]
    );
} elseif (isset($_POST['all_mod'])) {
    $db->exec("UPDATE `download__files` SET `type` = 2 WHERE `type` = '3'");
    echo $view->render(
        'system::pages/result',
        [
            'title'         => $title,
            'type'          => 'alert-success',
            'message'       => __('All files accepted'),
            'back_url'      => $urls['downloads'],
            'back_url_name' => __('Downloads'),
        ]
    );
}

$files = [];
$total = $db->query("SELECT COUNT(*) FROM `download__files` WHERE `type` = '3'")->fetchColumn();
if ($total) {
    $req_down = $db->query("SELECT * FROM `download__files` WHERE `type` = '3' ORDER BY `time` DESC LIMIT ${start}, " . $user->config->kmess);
    while ($res_down = $req_down->fetch()) {
        $file = Download::displayFile($res_down);
        $file['accept_url'] = '?act=mod_files&amp;id=' . $res_down['id'];
        $file['delete_url'] = '?act=delete_file&amp;id=' . $res_down['id'];
        $files[] = $file;
    }
}

echo $view->render(
    'downloads::files_moderation',
    [
        'title'      => $title,
        'page_title' => $title,
        'pagination' => $tools->displayPagination('?act=mod_files&amp;', $start, $total, $user->config->kmess),
        'files'      => $files ?? [],
        'total'      => $total,
        'urls'       => $urls,
    ]
);
