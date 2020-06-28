<?php

/**
 * This file is part of JohnCMS Content Management System.
 *
 * @copyright JohnCMS Community
 * @license   https://opensource.org/licenses/GPL-3.0 GPL-3.0
 * @link      https://johncms.com JohnCMS Project
 */

declare(strict_types=1);

defined('_IN_JOHNADM') || die('Error: restricted access');

/**
 * @var Johncms\System\Users\User $user
 * @var Johncms\NavChain $nav_chain
 * @var Johncms\System\Http\Request $request
 */

if ($user->rights < 9) {
    exit(__('Access denied'));
}

$title = __('Languages');
$nav_chain->add($title, '/admin/languages/');

$action = $request->getQuery('action', 'index', FILTER_SANITIZE_STRING);

$pages = [
    'index'   => 'index.php',
    'manage'  => 'manage.php',
    'delete'  => 'delete.php',
    'install' => 'install.php',
    'update'  => 'update.php',
];

if (array_key_exists($action, $pages)) {
    require __DIR__ . '/languages/' . $pages[$action];
} else {
    pageNotFound();
}
