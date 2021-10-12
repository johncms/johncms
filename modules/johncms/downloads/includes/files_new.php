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
 */

// Новые файлы
$title = __('New Files');
$sql_down = '';

if ($id) {
    $cat = $db->query("SELECT * FROM `download__category` WHERE `id` = '" . $id . "' LIMIT 1");
    $res_down_cat = $cat->fetch();

    if (! $cat->rowCount() || ! is_dir($res_down_cat['dir'])) {
        http_response_code(404);
        echo $view->render(
            'system::pages/result',
            [
                'title'         => $title,
                'type'          => 'alert-danger',
                'message'       => __('The directory does not exist'),
                'back_url'      => $urls['downloads'],
                'back_url_name' => __('Downloads'),
            ]
        );
        exit;
    }

    $title_pages = htmlspecialchars(mb_substr($res_down_cat['rus_name'], 0, 30));
    $sql_down = ' AND `dir` LIKE \'' . ($res_down_cat['dir']) . '%\' ';
}

$total = $db->query("SELECT COUNT(*) FROM `download__files` WHERE `type` = '2'  AND `time` > ${old} ${sql_down}")->fetchColumn();
// Выводим список
$files = [];
if ($total) {
    $i = 0;
    $req_down = $db->query("SELECT * FROM `download__files` WHERE `type` = '2'  AND `time` > ${old} ${sql_down} ORDER BY `time` DESC LIMIT ${start}, " . $user->config->kmess);
    while ($res_down = $req_down->fetch()) {
        $files[] = Download::displayFile($res_down);
    }
}

echo $view->render(
    'downloads::new_files',
    [
        'title'      => $title,
        'page_title' => $title,
        'pagination' => $tools->displayPagination('?id=' . $id . '&amp;act=new_files&amp;', $start, $total, $user->config->kmess),
        'show_user'  => $show_user ?? [],
        'files'      => $files ?? [],
        'total'      => $total,
        'urls'       => $urls,
    ]
);
