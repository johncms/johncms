<?php

$vote = abs(intval($_GET['img']));
if ($vote > 100)
    $vote = 100;
header("Content-type: image/gif");
$vote_img = imageCreateFromGIF("../images/vote.gif");
$color = imagecolorallocate($vote_img, 234, 237, 237);
$color2 = imagecolorallocate($vote_img, 227, 222, 222);
$color3 = imagecolorallocate($vote_img, 204, 200, 200);
$color4 = imagecolorallocate($vote_img, 185, 181, 181);
$color5 = imagecolorallocate($vote_img, 197, 195, 195);
imagefilledrectangle($vote_img, 1, 1, 100, 2, $color);
imagefilledrectangle($vote_img, 1, 3, 100, 4, $color2);
imagefilledrectangle($vote_img, 1, 5, 100, 6, $color3);
imagefilledrectangle($vote_img, 1, 7, 100, 8, $color4);
imagefilledrectangle($vote_img, 1, 9, 100, 10, $color5);
$color = imagecolorallocate($vote_img, 255, 204, 204);
$color2 = imagecolorallocate($vote_img, 255, 153, 153);
$color3 = imagecolorallocate($vote_img, 255, 102, 102);
$color4 = imagecolorallocate($vote_img, 255, 51, 51);
$color5 = imagecolorallocate($vote_img, 255, 102, 102);
$color6 = imagecolorallocate($vote_img, 0, 0, 0);
if ($vote > 0) {
    imagefilledrectangle($vote_img, 1, 1, $vote, 2, $color);
    imagefilledrectangle($vote_img, 1, 1, $vote, 2, $color);
    imagefilledrectangle($vote_img, 1, 3, $vote, 4, $color2);
    imagefilledrectangle($vote_img, 1, 5, $vote, 6, $color3);
    imagefilledrectangle($vote_img, 1, 7, $vote, 8, $color4);
    imagefilledrectangle($vote_img, 1, 9, $vote, 10, $color5);
}
imageString($vote_img, 1, 78, 2, "$vote%", $color6);
ImageGIF($vote_img);

?>