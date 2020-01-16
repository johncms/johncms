<?php

/**
 * This file is part of JohnCMS Content Management System.
 *
 * @copyright JohnCMS Community
 * @license   https://opensource.org/licenses/GPL-3.0 GPL-3.0
 * @link      https://johncms.com JohnCMS Project
 */

declare(strict_types=1);

use Library\Tree;
use Library\Utils;

if (! $id) {
    require_once 'index/main.php';
} elseif ($do === 'dir') {
// dir
    $actdir = $db->query(
        'SELECT `id`, `dir` FROM `library_cats` WHERE '
        . ($id !== null ? '`id` = ' . $id : 1) . ' LIMIT 1'
    )->fetch();

    if ($actdir['id'] > 0) {
        $actdir = $actdir['dir'];
    } else {
        Utils::redir404();
    }

    $dir_nav = new Tree($id);
    $dir_nav->processNavPanel();
    $dir_nav->printNavPanel();

    if ($actdir) {
        require_once 'index/sectionslist.php';
    } else {
        require_once 'index/booklist.php';
    }
} else {
    require_once 'index/book.php';
}
