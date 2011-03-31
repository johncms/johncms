<?php

/**
* @package     JohnCMS
* @link        http://johncms.com
* @copyright   Copyright (C) 2008-2011 JohnCMS Community
* @license     LICENSE.txt (see attached file)
* @version     VERSION.txt (see attached file)
* @author      http://johncms.com/about
*/

function format($name) {
    $f1 = strrpos($name, ".");
    $f2 = substr($name, $f1 + 1, 999);
    $fname = strtolower($f2);
    return $fname;
}

$file = isset($_GET['file']) ? htmlspecialchars(urldecode($_GET['file'])) : NULL;
if ($file && file_exists('../files/forum/attach/' . $file)) {
    $att_ext = strtolower(format('../files/forum/attach/' . $file));
    $pic_ext = array (
        'gif',
        'jpg',
        'jpeg',
        'png'
    );
    if (in_array($att_ext, $pic_ext)) {
        $sizs = GetImageSize('../files/forum/attach/' . $file);
        if ($sizs) {
            $razm = 50;
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
                case "gif":
                    $im = ImageCreateFromGIF('../files/forum/attach/' . $file);
                    break;

                case "jpg":
                    $im = ImageCreateFromJPEG('../files/forum/attach/' . $file);
                    break;

                case "jpeg":
                    $im = ImageCreateFromJPEG('../files/forum/attach/' . $file);
                    break;

                case "png":
                    $im = ImageCreateFromPNG('../files/forum/attach/' . $file);
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
}
?>