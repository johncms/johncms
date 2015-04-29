<?php

/**
* @package     JohnCMS
* @link        http://johncms.com
* @copyright   Copyright (C) 2008-2011 JohnCMS Community
* @license     LICENSE.txt (see attached file)
* @version     VERSION.txt (see attached file)
* @author      http://johncms.com/about
*/

define('_IN_JOHNCMS', 1);
require('../incfiles/core.php');
$textl = $lng['forum'];
require('../incfiles/head.php');
$map = new sitemap();
echo $map->forum_contents();
require('../incfiles/end.php');
