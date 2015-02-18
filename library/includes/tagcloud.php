<?php
/**
 * @package     JohnCMS
 * @link        http://johncms.com
 * @copyright   Copyright (C) 2008-2011 JohnCMS Community
 * @license     LICENSE.txt (see attached file)
 * @version     VERSION.txt (see attached file)
 * @author      http://johncms.com/about
 */
 
defined('_IN_JOHNCMS') or die('Error: restricted access');

$obj = new Hashtags();

$sort = isset($_GET['sort']) && $_GET['sort'] == 'rel' ? 'cmprang' : 'cmpalpha';

echo '<div class="phdr"><b><a href="?">' . $lng['library'] . '</a></b> | Облако тэгов</div>
<div class="bmenu">Сортировка по <a href="?act=tagcloud&amp;sort=rel">релевантности</a> | <a href="?act=tagcloud&amp;sort=alpha">алфавиту</a></div>';
echo '<div class="gmenu">' . $obj->cloud($obj->tag_rang($obj->array_cloudtags(), $sort)) . '</div>';