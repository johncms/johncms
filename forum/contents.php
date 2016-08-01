<?php

define('_IN_JOHNCMS', 1);
require('../incfiles/core.php');
$textl = $lng['forum'];
require('../incfiles/head.php');
$map = new sitemap();
echo $map->forum_contents();
require('../incfiles/end.php');
