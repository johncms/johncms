<?php

/**
 * This file is part of JohnCMS Content Management System.
 *
 * @copyright JohnCMS Community
 * @license   https://opensource.org/licenses/GPL-3.0 GPL-3.0
 * @link      https://johncms.com JohnCMS Project
 */

declare(strict_types=1);

use Johncms\UserProperties;

/** @var Johncms\System\Legacy\Tools $tools */
$tools = di(Johncms\System\Legacy\Tools::class);

// Принимаем данные, выводим форму поиска
$search_post = isset($_POST['search']) ? trim($_POST['search']) : false;
$search_get = isset($_GET['search']) ? rawurldecode(trim($_GET['search'])) : '';
$search = $search_post ? $search_post : $search_get;

$data = [];
$title = __('User Search');

$nav_chain->add($title);

$data['search_query'] = $tools->checkout($search);

// Проверям на ошибки
$error = [];
if (! empty($search) && (mb_strlen($search) < 2 || mb_strlen($search) > 20)) {
    $error[] = __('Nickname') . ': ' . __('Invalid length');
}

if (preg_match("/[^1-9a-z\-\@\*\(\)\?\!\~\_\=\[\]]+/", $tools->rusLat($search))) {
    $error[] = __('Nickname') . ': ' . __('Invalid characters');
}

if ($search && ! $error) {
    /** @var PDO $db */
    $db = di(PDO::class);

    // Выводим результаты поиска
    $search_db = $tools->rusLat($search);
    $search_db = strtr(
        $search_db,
        [
            '_' => '\\_',
            '%' => '\\%',
        ]
    );
    $search_db = '%' . $search_db . '%';
    $total = $db->query('SELECT COUNT(*) FROM `users` WHERE `name_lat` LIKE ' . $db->quote($search_db))->fetchColumn();
    if ($total) {
        $req = $db->query('SELECT * FROM `users` WHERE `name_lat` LIKE ' . $db->quote($search_db) . " ORDER BY `name` ASC LIMIT ${start}, " . $user->config->kmess);
        $data['list'] = static function () use ($req, $user) {
            while ($res = $req->fetch()) {
                $res['user_id'] = $res['id'];
                $user_properties = new UserProperties();
                $user_data = $user_properties->getFromArray($res);
                $res = array_merge($res, $user_data);
                yield $res;
            }
        };
    }
    $data['pagination'] = $tools->displayPagination('?search=' . urlencode($search) . '&amp;', $start, $total, $user->config->kmess);
}

$data['errors'] = $error;
$data['total'] = $total ?? 0;

echo $view->render(
    'users::search',
    [
        'title'      => $title,
        'page_title' => $title,
        'data'       => $data,
    ]
);
