<?php

define('_IN_JOHNCMS', 1);
require('../incfiles/core.php');
$textl = $lng['library'];
require('../incfiles/head.php');
$map = new sitemap();
echo $map->library_contents();
require('../incfiles/end.php');