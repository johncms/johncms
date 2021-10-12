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

defined('_IN_JOHNCMS') || die('Error: restricted access');

/** @var LengthAwarePaginator $users */
$users = (new User())
    ->approved()
    ->where('dayb', '=', date('j'))
    ->where('monthb', '=', date('n'))
    ->paginate($user->config->kmess);

$nav_chain->add(__('Birthdays'));

echo $view->render(
    'users::users',
    [
        'pagination' => $users->render(),
        'title'      => __('Birthdays'),
        'page_title' => __('Birthdays'),
        'total'      => $users->total(),
        'list'       => $users->items(),
    ]
);
