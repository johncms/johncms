<?php

/*
 * This file is part of JohnCMS Content Management System.
 *
 * @copyright JohnCMS Community
 * @license   https://opensource.org/licenses/GPL-3.0 GPL-3.0
 * @link      https://johncms.com JohnCMS Project
 */

declare(strict_types=1);

defined('_IN_JOHNCMS') || die('Error: restricted access');

$obj = new Library\Hashtags();

$sort = isset($_GET['sort']) && $_GET['sort'] === 'rel' ? 'cmprang' : 'cmpalpha';

$menu[] = ($sort === 'cmpalpha' ? '<strong>' . __('Sorted by alphabetical') . '</strong>' : '<a href="?act=tagcloud&amp;sort=alpha">' . __('Sorted by alphabetical') . '</a>');
$menu[] = ($sort === 'cmprang' ? '<strong>' . __('Sorted by relevance') . '</strong>' : '<a href="?act=tagcloud&amp;sort=rel">' . __('Sorted by relevance') . '</a>');
$menu = implode(' | ', $menu);

$cloud = $obj->getCache($sort);

echo $view->render(
    'library::tagcloud',
    [
        'menu'  => $menu,
        'cloud' => $cloud,
    ]
);
