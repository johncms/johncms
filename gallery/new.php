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

echo '<div class="phdr">' . $lng_gal['new_photo'] . '</div>';
$stmt = $db->query("select * from `gallery` where time > '" . (time() - 259200) . "' and type='ft' order by time desc;");
$totalnew = $stmt->rowCount();
if (empty($_GET['page'])) {
    $page = 1;
} else {
    $page = intval($_GET['page']);
}
$start = $page * $kmess - $kmess;
if ($totalnew < $start + $kmess) {
    $end = $totalnew;
} else {
    $end = $start + $kmess;
}
if ($totalnew != 0) {
    while ($newf = $stmt->fetch()) {
        if ($i >= $start && $i < $end) {
            $d = $i / 2;
            $d1 = ceil($d);
            $d2 = $d1 - $d;
            $d3 = ceil($d2);
            if ($d3 == 0) {
                $div = "<div class='c'>";
            } else {
                $div = "<div class='b'>";
            }
            echo "$div<br/>&#160;<a href='index.php?id=" . $newf['id'] . "'>";
            $infile = "foto/$newf[name]";
            if (!empty($_SESSION['frazm'])) {
                $razm = $_SESSION['frazm'];
            } else {
                $razm = 50;
            }
            $sizs = GetImageSize($infile);
            $width = $sizs[0];
            $height = $sizs[1];
            $quality = 100;
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
            $format = functions::format($infile);
            switch ($format) {
                case "gif":
                    $im = ImageCreateFromGIF($infile);
                    break;

                case "jpg":
                    $im = ImageCreateFromJPEG($infile);
                    break;

                case "jpeg":
                    $im = ImageCreateFromJPEG($infile);
                    break;

                case "png":
                    $im = ImageCreateFromPNG($infile);
                    break;
            }
            $im1 = imagecreatetruecolor($tn_width, $tn_height);
            $namefile = "$newf[name]";
            imagecopyresized($im1, $im, 0, 0, 0, 0, $tn_width, $tn_height, $width, $height);
            switch ($format) {
                case "gif":
                    $imagnam = "temp/$namefile.temp.gif";
                    ImageGif($im1, $imagnam, $quality);
                    echo "<img src='" . $imagnam . "' alt=''/><br/>";
                    break;

                case "jpg":
                    $imagnam = "temp/$namefile.temp.jpg";
                    imageJpeg($im1, $imagnam, $quality);
                    echo "<img src='" . $imagnam . "' alt=''/><br/>";
                    break;

                case "jpeg":
                    $imagnam = "temp/$namefile.temp.jpg";
                    imageJpeg($im1, $imagnam, $quality);
                    echo "<img src='" . $imagnam . "' alt=''/><br/>";

                    break;

                case "png":
                    $imagnam = "temp/$namefile.temp.png";
                    imagePng($im1, $imagnam, $quality);
                    echo "<img src='" . $imagnam . "' alt=''/><br/>";

                    break;
            }
            imagedestroy($im);
            imagedestroy($im1);
            $kom1 = $db->query("select COUNT(*) from `gallery` where type='km' and refid='" . $newf['id'] . "';")->fetchColumn();
            echo "</a><br/>" . $lng['date'] . ': ' . functions::display_date($newf['time']) . '<br/>' . $lng['description'] . ": " . _e($newf['text']) . "<br/>";
            $al1 = $db->query("select * from `gallery` where type = 'al' and id = '" . $newf['refid'] . "' LIMIT 1;")->fetch();
            $rz1 = $db->query("select * from `gallery` where type = 'rz' and id = '" . $al1['refid'] . "' LIMIT 1;")->fetch();
            echo '<a href="index.php?id=' . $al1['id'] . '">' . _e($rz1['text']) . '&#160;/&#160;' . _e($al1['text']) . '</a></div>';
        }
        ++$i;
    }
    if ($totalnew > 10) //TODO: Переделать на новый листинг по страницам
    {
        echo "<hr/>";
        echo functions::display_pagination('index.php?act=new&amp;', $start, $total, $kmess);
        echo
            "<form action='index.php'>" . $lng['to_page'] . ":<br/><input type='hidden' name='act' value='new'/><input type='text' name='page'/><br/><input type='submit' value='Go!'/></form>";
    }
    echo "<br/>" . $lng['total'] . ": $totalnew";
} else {
    echo '<p>' . $lng['list_empty'] . '</p>';
}
echo "<br/><a href='index.php?'>" . $lng['to_gallery'] . "</a><br/>";