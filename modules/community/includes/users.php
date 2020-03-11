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

/**
 * @var PDO $db
 * @var Johncms\System\Legacy\Tools $tools
 * @var Johncms\System\Users\User $user
 * @var Johncms\System\View\Render $view
 */

/** @var LengthAwarePaginator $users */
$users = (new User())->approved()->paginate($user->config->kmess);

$nav_chain->add(__('List of users'));

echo $view->render(
    'users::users',
    [
        'pagination' => $users->render(),
        'title'      => __('List of users'),
        'page_title' => __('List of users'),
        'total'      => $users->total(),
        'list'       => $users->items(),
    ]
);
