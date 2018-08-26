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

require_once("../incfiles/head.php");

$im = array();
$delimag = opendir("$filesroot/graftemp");
while ($imd = readdir($delimag)) {
    if ($imd != "." && $imd != ".." && $imd != "index.php") {
        $im[] = $imd;
    }
}
closedir($delimag);
$totalim = count($im);
for ($imi = 0; $imi < $totalim; $imi++) {
    $filtime[$imi] = filemtime("$filesroot/graftemp/$im[$imi]");
    $tim = time();
    $ftime1 = $tim - 10;
    if ($filtime[$imi] < $ftime1) {
        unlink("$filesroot/graftemp/$im[$imi]");
    }
}
if (!$file) {
    echo functions::display_error($lng_dl['file_not_selected'], '<a href="index.php">' . $lng['back'] . '</a>');
    require_once('../incfiles/end.php');
    exit;
}
$error = true;
$stmt = $db->query("select * from `download` where type = 'file' and id = '" . $file . "' LIMIT 1;");
if ($stmt->rowCount()) {
    $adrfile = $stmt->fetch();
    if (is_file($adrfile['adres'] . '/' . $adrfile['name'])) {
        $error = false;
    }
}
if ($error) {
    echo functions::display_error($lng_dl['file_select_error'], '<a href="index.php">' . $lng['back'] . '</a>');
    require_once('../incfiles/end.php');
    exit;
}
$_SESSION['downl'] = rand(1000, 9999);
$siz = filesize("$adrfile[adres]/$adrfile[name]");
$siz = round($siz / 1024, 2);
$filtime = filemtime("$adrfile[adres]/$adrfile[name]");
$filtime = date("d.m.Y", $filtime);

$dnam1 = $db->query("select * from `download` where type = 'cat' and id = '" . $adrfile['refid'] . "' LIMIT 1;")->fetch();
$dirname = "$dnam1[text]";
$dirid = "$dnam1[id]";
$nadir = $adrfile['refid'];
echo '<div class="phdr"><a href="index.php"><b>' . $lng['downloads'] . '</b></a>';
// Получаем структуру каталогов
while ($nadir != "" && $nadir != "0") {
    echo ' | <a href="?cat=' . $nadir . '">' . $dirname . '</a><br/>';
    $dnamm1 = $db->query("select * from `download` where type = 'cat' and id = '" . $nadir . "' LIMIT 1;")->fetch();
    $dnamm3 = $db->query("select * from `download` where type = 'cat' and id = '" . $dnamm1['refid'] . "' LIMIT 1;")->fetch();
    $nadir = $dnamm1['refid'];
    $dirname = $dnamm3['text'];
}
echo '</div><div class="menu"><p>';
echo '<b>' . $lng_dl['file'] . ': <span class="red">' . $adrfile['name'] . '</span></b><br/>' .
    '<b>' . $lng_dl['uploaded'] . ':</b> ' . $filtime . '<br/>';

$graf = array
(
    "gif",
    "jpg",
    "png"
);
$prg = strtolower(functions::format($adrfile['name']));
if (in_array($prg, $graf)) {
    $sizsf = GetImageSize("$adrfile[adres]/$adrfile[name]");
    $widthf = $sizsf[0];
    $heightf = $sizsf[1];
    #  !предпросмотр!
    $namefile = $adrfile['name'];
    $infile = "$adrfile[adres]/$namefile";
    if (!empty($_SESSION['razm'])) {
        $razm = $_SESSION['razm'];
    } else {
        $razm = 110;
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
    switch ($prg) {
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
    $im1 = ImageCreateTrueColor($tn_width, $tn_height);
    imagecopyresized($im1, $im, 0, 0, 0, 0, $tn_width, $tn_height, $width, $height);
    $path = "$filesroot/graftemp";
    $imagnam = "$path/$namefile.temp.png";
    imageJpeg($im1, $imagnam, $quality);
    echo "<p><img src='" . $imagnam . "' alt=''/></p>";
    imagedestroy($im);
    imagedestroy($im1);
    @chmod("$imagnam", 0644);
    echo $widthf . ' x ' . $heightf . 'px<br/>';
}

if ($prg == "mp3") {
    $id3 = new MP3_Id();
    $result = $id3->read("$adrfile[adres]/$adrfile[name]");
    $result = $id3->study();
    echo '<p>';
    if (!empty($id3->artists))
        echo '<div><b>' . $lng_dl['artist'] . ':</b> ' . $id3->artists . '</div>';
    if (!empty($id3->album))
        echo '<div><b>' . $lng_dl['album'] . ':</b> ' . $id3->album . '</div>';
    if (!empty($id3->year))
        echo '<div><b>' . $lng_dl['released'] . ':</b> ' . $id3->year . '</div>';
    if (!empty($id3->name))
        echo '<div><b>' . $lng['title'] . ':</b> ' . $id3->name . '</div>';
    echo '</p>';
    if ($id3->getTag('bitrate')) {
        echo '<b>' . $lng_dl['bitrate'] . ':</b> ' . $id3->getTag('bitrate') . ' kBit/sec<br/>' .
            '<b>' . $lng_dl['duration'] . ':</b> ' . $id3->getTag('length') . '<br/>';
    }
}
if (!empty($adrfile['text'])) {
    echo "<p>Описание:<br/>$adrfile[text]</p>";
}

if ((!in_array($prg, $graf)) && ($prg != "mp3")) {
    if (!empty($adrfile['screen'])) {
        $infile = "$screenroot/$adrfile[screen]";
        if (!empty($_SESSION['razm'])) {
            $razm = $_SESSION['razm'];
        } else {
            $razm = 110;
        }
        $sizs = GetImageSize($infile);
        $width = $sizs[0];
        $height = $sizs[1];
        $quality = 100;
        $angle = 0;
        $fontsiz = 20;
        $tekst = $set['copyright'];
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
        $color = imagecolorallocate($im, 55, 255, 255);
        $fontdir = opendir("$filesroot/fonts");
        while ($ttf = readdir($fontdir)) {
            if ($ttf != "." && $ttf != ".." && $ttf != "index.php") {
                $arr[] = $ttf;
            }
        }
        $it = count($arr);
        $ii = rand(0, $it - 1);
        $fontus = "$filesroot/fonts/$arr[$ii]";
        $font_size = ceil(($width + $height) / 15);
        @imagettftext($im, $font_size, $angle, '10', $height - 10, $color, $fontus, $tekst);
        $im1 = imagecreatetruecolor($tn_width, $tn_height);
        $namefile = "$adrfile[name]";
        imagecopyresized($im1, $im, 0, 0, 0, 0, $tn_width, $tn_height, $width, $height);
        $path = "$filesroot/graftemp";
        switch ($format) {
            case "gif":
                $imagnam = "$path/$namefile.temp.gif";
                ImageGif($im1, $imagnam, $quality);
                echo "<p><img src='" . $imagnam . "' alt=''/></p>";
                break;

            case "jpg":
                $imagnam = "$path/$namefile.temp.jpg";
                imageJpeg($im1, $imagnam, $quality);
                echo "<p><img src='" . $imagnam . "' alt=''/></p>";
                break;

            case "jpeg":
                $imagnam = "$path/$namefile.temp.jpg";
                imageJpeg($im1, $imagnam, $quality);
                echo "<p><img src='" . $imagnam . "' alt=''/></p>";

                break;

            case "png":
                $imagnam = "$path/$namefile.temp.png";
                imagePng($im1, $imagnam, $quality);
                echo "<p><img src='" . $imagnam . "' alt=''/></p>";
                break;
        }
        imagedestroy($im);
        imagedestroy($im1);
    }
}

// Ссылка на скачивание файла
$dl_count = !empty($adrfile['ip']) ? intval($adrfile['ip']) : 0;
echo '</p></div><div class="gmenu"><p>' .
    '<h3 class="red">' .
    '<a href="index.php?act=down&amp;id=' . $file . '"><img src="../images/file.gif" border="0" alt=""/></a>&#160;' .
    '<a href="index.php?act=down&amp;id=' . $file . '">' . $lng['download'] . '</a></h3>' .
    '<small><span class="gray">' . $lng_dl['size'] . ':</span> <b>' . $siz . '</b> kB<br />';
if ($prg == "zip") {
    echo "<a href='?act=zip&amp;file=" . $file . "'>Открыть архив</a><br/>";
}
echo '<span class="gray">' . $lng_dl['downloads'] . ':</span> <b>' . $dl_count . '</b>';

if (!empty($adrfile['soft'])) {
    $rating = unserialize($adrfile['soft']);
    $rat = $rating['vote'] / $rating['count'];
    $rat = round($rat, 2);
    echo '<br /><span class="gray">' . $lng_dl['average_rating'] . ':</span> <b>' . $rat . '</b>' .
        '<br /><span class="gray">' . $lng_dl['vote_count'] . ':</span> <b>' . $rating['count'] . '</b>';
}

echo '</small></p>';

// Рейтинг файла
echo '<p><form action="index.php?act=rat&amp;id=' . $file . '" method="post"><select name="rat" style="font-size: x-small;">';
for ($i = 10; $i >= 1; --$i) {
    echo "<option>$i</option>";
}
echo '</select><input type="submit" value="' . $lng_dl['rate'] . '" style="font-size: x-small;"/></form></p>';

if ($set['mod_down_comm'] || $rights >= 7) {
    $totalkomm = $db->query("SELECT COUNT(*) FROM `download` WHERE `type` = 'komm' AND `refid` = '$file'")->fetchColumn();
    echo '<p><small><a href="index.php?act=komm&amp;id=' . $file . '">' . $lng['comments'] . '</a> (' . $totalkomm . ')</small></p>';
}

echo '</div>';
echo '<div class="phdr"><a href="index.php">' . $lng['downloads'] . '</a></div>';
if (($rights == 4 || $rights >= 6) && (!empty($_GET['file']))) {
    echo '<p>';
    if ((!in_array($prg, $graf)) && ($prg != "mp3")) {
        echo '<a href="index.php?act=screen&amp;file=' . $file . '">' . $lng_dl['change_screenshot'] . '</a><br/>';
    }
    echo '<a href="index.php?act=opis&amp;file=' . $file . '">' . $lng_dl['change_description'] . '</a><br/>';
    echo '<a href="index.php?act=dfile&amp;file=' . $file . '">' . $lng_dl['delete_file'] . '</a>';
    echo '</p>';
}