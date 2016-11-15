<?php

defined('_IN_JOHNCMS') or die('Error: restricted access');

$obj = new Library\Hashtags();

$sort = isset($_GET['sort']) && $_GET['sort'] == 'rel' ? 'cmprang' : 'cmpalpha';

$menu[] = $sort == 'cmpalpha' ? '<strong>' . _t('Sorted by alphabetical') . '</strong>' : '<a href="?act=tagcloud&amp;sort=alpha">' . _t('Sorted by alphabetical') . '</a>';
$menu[] = $sort == 'cmprang' ? '<strong>' . _t('Sorted by relevance') . '</strong>' : '<a href="?act=tagcloud&amp;sort=rel">' . _t('Sorted by relevance') . '</a> ';

echo '<div class="phdr">' .
    '<strong><a href="?">' . _t('Library') . '</a></strong> | ' . _t('Tag Cloud') . '</div>' .
    '<div class="topmenu">' . _t('Sort') . ': ' . implode(' | ', $menu) . '</div>' .
    '<div class="gmenu">' . $obj->getCache($sort) . '</div>' .
    '<p><a href="?">' . _t('To Library') . '</a></p>';
