<?php

/*
////////////////////////////////////////////////////////////////////////////////
// JohnCMS                Mobile Content Management System                    //
// Project site:          http://johncms.com                                  //
// Support site:          http://gazenwagen.com                               //
////////////////////////////////////////////////////////////////////////////////
// Lead Developer:        Oleg Kasyanov   (AlkatraZ)  alkatraz@gazenwagen.com //
// Development Team:      Eugene Ryabinin (john77)    john77@gazenwagen.com   //
//                        Dmitry Liseenko (FlySelf)   flyself@johncms.com     //
////////////////////////////////////////////////////////////////////////////////
*/

$u = isset($_GET['u']) ? abs(intval($_GET['u'])) : NULL;
$file = isset($_GET['f']) ? htmlspecialchars(urldecode($_GET['f'])) : NULL;
$filepath = '../files/users/album/' . $u . '/' . $file;
if ($u && $file && file_exists($filepath)) {
    $att_ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
    $pic_ext = array (
        'gif',
        'jpg',
        'jpeg',
        'png'
    );
    if (in_array($att_ext, $pic_ext)) {
        $sizs = GetImageSize($filepath);
        $razm = 230;
        $width = $sizs[0];
        $height = $sizs[1];
        $x_ratio = $razm / $width;
        $y_ratio = $razm / $height;
        if (($width <= $razm) && ($height <= $razm)) {
            $tn_width = $width;
            $tn_height = $height;
        } else if (($x_ratio * $height) < $razm) {
            $tn_height = ceil($x_ratio * $height);
            $tn_width = $razm;
        } else {
            $tn_width = ceil($y_ratio * $width);
            $tn_height = $razm;
        }
        switch ($att_ext) {
            case 'gif':
                $im = ImageCreateFromGIF($filepath);
                break;

            case 'jpg':
            case 'jpeg':
                $im = ImageCreateFromJPEG($filepath);
                break;

            case 'png':
                $im = ImageCreateFromPNG($filepath);
                break;
        }
        $im1 = imagecreatetruecolor($tn_width, $tn_height);
        imagecopyresized($im1, $im, 0, 0, 0, 0, $tn_width, $tn_height, $width, $height);
        // Передача изображения в Браузер
        ob_start();
        imageJpeg($im1, NULL, 60);
        ImageDestroy($im);
        imagedestroy($im1);
        header('Content-Type: image/jpeg');
        header('Content-Disposition: inline; filename=thumbinal.jpg');
        header('Content-Length: ' . ob_get_length());
        ob_end_flush();
    }
}
