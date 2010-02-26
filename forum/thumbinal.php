<?php

/*
////////////////////////////////////////////////////////////////////////////////
// JohnCMS                                                                    //
// Официальный сайт сайт проекта:      http://johncms.com                     //
// Дополнительный сайт поддержки:      http://gazenwagen.com                  //
////////////////////////////////////////////////////////////////////////////////
// JohnCMS core team:                                                         //
// Евгений Рябинин aka john77          john77@johncms.com                     //
// Олег Касьянов aka AlkatraZ          alkatraz@johncms.com                   //
//                                                                            //
// Информацию о версиях смотрите в прилагаемом файле version.txt              //
////////////////////////////////////////////////////////////////////////////////
*/

function format($name) {
    $f1 = strrpos($name, ".");
    $f2 = substr($name, $f1 + 1, 999);
    $fname = strtolower($f2);
    return $fname;
}

$file = isset ($_GET['file']) ? htmlspecialchars(urldecode($_GET['file'])) : NULL;
if ($file && file_exists('./files/' . $file)) {
    $att_ext = strtolower(format('./files/' . $file));
    $pic_ext = array('gif', 'jpg', 'jpeg', 'png');
    if (in_array($att_ext, $pic_ext)) {
        $sizs = GetImageSize('./files/' . $file);
        $razm = 50;
        $width = $sizs[0];
        $height = $sizs[1];
        $x_ratio = $razm / $width;
        $y_ratio = $razm / $height;
        if (($width <= $razm) && ($height <= $razm)) {
            $tn_width = $width;
            $tn_height = $height;
        }
        else
            if (($x_ratio * $height) < $razm) {
                $tn_height = ceil($x_ratio * $height);
                $tn_width = $razm;
            }
            else {
                $tn_width = ceil($y_ratio * $width);
                $tn_height = $razm;
        }
        switch ($att_ext) {
            case "gif" :
                $im = ImageCreateFromGIF('./files/' . $file);
                break;
            case "jpg" :
                $im = ImageCreateFromJPEG('./files/' . $file);
                break;
            case "jpeg" :
                $im = ImageCreateFromJPEG('./files/' . $file);
                break;
            case "png" :
                $im = ImageCreateFromPNG('./files/' . $file);
                break;
        }
        $im1 = imagecreatetruecolor($tn_width, $tn_height);
        imagecopyresized($im1, $im, 0, 0, 0, 0, $tn_width, $tn_height, $width, $height);
        // Передача изображения в Браузер
        ob_start();
        imageJpeg($im1, NULL, 60);
        ImageDestroy($im);
        imagedestroy($im1);
        header("Content-Type: image/jpeg");
        header('Content-Disposition: inline; filename=thumbinal.jpg');
        header('Content-Length: ' . ob_get_length());
        ob_end_flush();
    }
}

?>