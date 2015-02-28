<?php
/**
 * @package     JohnCMS
 * @link        http://johncms.com
 * @copyright   Copyright (C) 2008-2015 JohnCMS Community
 * @license     LICENSE.txt (see attached file)
 * @version     VERSION.txt (see attached file)
 * @author      http://johncms.com/about
 */

defined('_IN_JOHNCMS') or die('Error: restricted access');

$obj = new Hashtags();

$sort = isset($_GET['sort']) && $_GET['sort'] == 'rel' ? 'cmprang' : 'cmpalpha';

echo '<div class="phdr">' .
    '<b><a href="?">' . $lng['library'] . '</a></b> | ' . $lng_lib['cloud_of_tags'] . '</div>' .
    '<div class="topmenu">' . $lng_lib['sort'] . ': <a href="?act=tagcloud&amp;sort=rel">' . $lng_lib['relevance'] . '</a> | <a href="?act=tagcloud&amp;sort=alpha">' . $lng_lib['alphabet'] . '</a></div>' .
    '<div class="gmenu">' . $obj->get_cache($sort) . '</div>' .
    '<div class="phdr"><a href="?">' . $lng_lib['to_library'] . '</a></div>';
