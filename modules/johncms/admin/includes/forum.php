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
 * @var PDO $db
 * @var Johncms\System\Legacy\Tools $tools
 * @var Johncms\Users\User $user
 * @var Johncms\NavChain $nav_chain
 * @var \Johncms\Http\Request $request
 */

$title = __('Forum Management');
$nav_chain->add($title, '/admin/forum/');

$mod = $request->getQuery('mod', 'index');

$set_forum = unserialize($user->set_forum, ['allowed_classes' => false]);
if (! isset($set_forum) || empty($set_forum)) {
    $set_forum = [
        'farea'    => 0,
        'upfp'     => 0,
        'farea_w'  => 20,
        'farea_h'  => 4,
        'postclip' => 1,
        'postcut'  => 2,
    ];
}

$pages = [
    'index',
    'del',
    'add',
    'edit',
    'cat',
    'htopics',
    'hposts',
    'settings',
];

if (($key = array_search($mod, $pages)) !== false) {
    require __DIR__ . '/forum/' . $pages[$key] . '.php';
} else {
    pageNotFound();
}
