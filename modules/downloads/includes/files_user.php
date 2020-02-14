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
use Psr\Http\Message\ServerRequestInterface;

defined('_IN_JOHNCMS') || die('Error: restricted access');

/**
 * @var PDO $db
 * @var Johncms\System\Legacy\Tools $tools
 * @var Johncms\System\Users\User $user
 * @var ServerRequestInterface $request
 */

$title = __('User Files');

$request = di(ServerRequestInterface::class);
$get = $request->getQueryParams();

$id = isset($get['id']) ? (int) $get['id'] : 0;
$req = $db->query('SELECT * FROM `users` WHERE `id` = ' . $id);

if (! $show_user = $req->fetch()) {
    http_response_code(404);
    echo $view->render(
        'system::pages/result',
        [
            'title'         => __('Downloads'),
            'type'          => 'alert-danger',
            'message'       => __('User does not exists'),
            'back_url'      => $urls['downloads'],
            'back_url_name' => __('Downloads'),
        ]
    );
    exit;
}

$total = $db->query("SELECT COUNT(*) FROM `download__files` WHERE `type` = '2'  AND `user_id` = " . $id)->fetchColumn();
// Список файлов
$files = [];
if ($total) {
    $req_down = $db->query("SELECT * FROM `download__files` WHERE `type` = '2'  AND `user_id` = " . $id . " ORDER BY `time` DESC LIMIT ${start}, " . $user->config->kmess);
    while ($res_down = $req_down->fetch()) {
        $files[] = Download::displayFile($res_down);
    }
}

$show_user['user_profile_link'] = '';
if (! empty($show_user['id']) && $user->isValid() && $user->id !== $show_user['id']) {
    $show_user['user_profile_link'] = '/profile/?user=' . $show_user['id'];
}

$show_user['user_rights_name'] = '';
if (! empty($show_user['rights'])) {
    $show_user['user_rights_name'] = $user_rights_names[$show_user['rights']] ?? '';
}

$show_user['user_is_online'] = time() <= $show_user['lastdate'] + 300;
$show_user['search_ip_url'] = '/admin/search_ip/?ip=' . long2ip((int) $show_user['ip']);
$show_user['ip'] = long2ip((int) $show_user['ip']);
$show_user['search_ip_via_proxy_url'] = '/admin/search_ip/?ip=' . long2ip((int) $show_user['ip_via_proxy']);
$show_user['ip_via_proxy'] = ! empty($show_user['ip_via_proxy']) ? long2ip((int) $show_user['ip_via_proxy']) : 0;

echo $view->render(
    'downloads::files_user',
    [
        'title'      => $title,
        'page_title' => $title,
        'pagination' => $tools->displayPagination('?act=user_files&amp;id=' . $id . '&amp;', $start, $total, $user->config->kmess),
        'show_user'  => $show_user ?? [],
        'files'      => $files ?? [],
        'total'      => $total,
        'urls'       => $urls,
    ]
);
