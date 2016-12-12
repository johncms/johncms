<?php
/*
 * JohnCMS NEXT Mobile Content Management System (http://johncms.com)
 *
 * For copyright and license information, please see the LICENSE.md
 * Installing the system or redistributions of files must retain the above copyright notice.
 *
 * @link        http://johncms.com JohnCMS Project
 * @copyright   Copyright (C) JohnCMS Community
 * @license     GPL-3
 */

function format($name)
{
    $f1 = strrpos($name, ".");
    $f2 = substr($name, $f1 + 1, 999);
    $fname = strtolower($f2);
    return $fname;
}

$u = isset($_GET['u']) ? abs(intval($_GET['u'])) : NULL;
$file = isset($_GET['f']) ? htmlspecialchars(urldecode($_GET['f'])) : NULL;

if ($u && $file && file_exists('../files/users/album/' . $u . '/' . $file)) {
    $att_ext = strtolower(format('../files/users/album/' . $u . '/' . $file));
    $pic_ext = array(
        'gif',
        'jpg',
        'jpeg',
        'png'
    );

    if (in_array($att_ext, $pic_ext)) {
        $sizs = GetImageSize('../files/users/album/' . $u . '/' . $file);
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
            case "gif":
                $im = ImageCreateFromGIF('../files/users/album/' . $u . '/' . $file);
                break;

            case "jpg":
                $im = ImageCreateFromJPEG('../files/users/album/' . $u . '/' . $file);
                break;

            case "jpeg":
                $im = ImageCreateFromJPEG('../files/users/album/' . $u . '/' . $file);
                break;

            case "png":
                $im = ImageCreateFromPNG('../files/users/album/' . $u . '/' . $file);
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
