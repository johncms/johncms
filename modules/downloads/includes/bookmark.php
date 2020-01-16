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


if (! $user->isValid()) {
    http_response_code(403);
    echo $view->render(
        'system::pages/result',
        [
            'title'   => __('Downloads'),
            'type'    => 'alert-danger',
            'message' => __('For registered users only'),
        ]
    );
    exit;
}

$nav_chain->add(__('Favorites'));
$total = $db->query('SELECT COUNT(*) FROM `download__bookmark` WHERE `user_id` = ' . $user->id)->fetchColumn();

// Список закладок
if ($total) {
    $req_down = $db->query(
        'SELECT `download__files`.*, `download__bookmark`.`id` AS `bid`
    FROM `download__files` LEFT JOIN `download__bookmark` ON `download__files`.`id` = `download__bookmark`.`file_id`
    WHERE `download__bookmark`.`user_id`=' . $user->id . " ORDER BY `download__files`.`time` DESC LIMIT ${start}, " . $user->config->kmess
    );
    $files = [];
    while ($res_down = $req_down->fetch()) {
        $files[] = Download::displayFile($res_down);
    }
}

echo $view->render(
    'downloads::bookmarks',
    [
        'title'       => __('Favorites'),
        'page_title'  => __('Favorites'),
        'pagination'  => $tools->displayPagination('?act=bookmark&amp;', $start, $total, $user->config->kmess),
        'files'       => $files ?? [],
        'total_files' => $total,
        'urls'        => $urls,
    ]
);
