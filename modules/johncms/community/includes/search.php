<?php

/**
 * This file is part of JohnCMS Content Management System.
 *
 * @copyright JohnCMS Community
 * @license   https://opensource.org/licenses/GPL-3.0 GPL-3.0
 * @link      https://johncms.com JohnCMS Project
 */

declare(strict_types=1);

use Illuminate\Pagination\LengthAwarePaginator;
use Johncms\Users\User;

/** @var Johncms\System\Legacy\Tools $tools */
$tools = di(Johncms\System\Legacy\Tools::class);

// Принимаем данные, выводим форму поиска
$search = isset($_GET['search']) ? rawurldecode(trim($_GET['search'])) : '';

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
    $search_db = '%' . $search_db . '%';
    /** @var LengthAwarePaginator $users */
    $users = (new User())
        ->approved()
        ->where('name_lat', 'LIKE', $search_db)
        ->orderBy('name')
        ->paginate($user->config->kmess);

    $total = $users->total();
    $data['list'] = $users->items();
    $data['pagination'] = $users->render();
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
