<?php

declare(strict_types=1);

/*
 * This file is part of JohnCMS Content Management System.
 *
 * @copyright JohnCMS Community
 * @license   https://opensource.org/licenses/GPL-3.0 GPL-3.0
 * @link      https://johncms.com JohnCMS Project
 */

$vote = abs((int) ($_GET['img']));

if ($vote > 100) {
    $vote = 100;
}

header('Content-type: image/gif');
$vote_img = imagecreatefromgif('../images/download/vote.gif');
$color = imagecolorallocate($vote_img, 0, 255, 0);
$color2 = imagecolorallocate($vote_img, 255, 153, 153);
$color3 = imagecolorallocate($vote_img, 255, 102, 102);
$color4 = imagecolorallocate($vote_img, 255, 51, 51);
$color5 = imagecolorallocate($vote_img, 255, 102, 102);
$color6 = imagecolorallocate($vote_img, 0, 0, 0);
imagefilledrectangle($vote_img, 0, 0, $vote, 5, $color);
ob_start();
imagegif($vote_img, null, 100);
imagedestroy($vote_img);
header('Content-Type: image/gif');
header('Content-Disposition: inline; filename=vote.gif');
header('Content-Length: ' . ob_get_length());
ob_end_flush();
