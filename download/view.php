<?php

/*
////////////////////////////////////////////////////////////////////////////////
// JohnCMS                             Content Management System              //
// Официальный сайт сайт проекта:      http://johncms.com                     //
// Дополнительный сайт поддержки:      http://gazenwagen.com                  //
////////////////////////////////////////////////////////////////////////////////
// JohnCMS core team:                                                         //
// Евгений Рябинин aka john77          john77@gazenwagen.com                  //
// Олег Касьянов aka AlkatraZ          alkatraz@gazenwagen.com                //
//                                                                            //
// Информацию о версиях смотрите в прилагаемом файле version.txt              //
////////////////////////////////////////////////////////////////////////////////
*/

defined('_IN_JOHNCMS') or die('Error: restricted access');

require_once ("../incfiles/head.php");
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
if ($_GET['file'] == "") {
    echo "Не выбран файл<br/><a href='?'>К категориям</a><br/>";
    require_once ('../incfiles/end.php');
    exit;
}
$file = intval(trim($_GET['file']));
$file1 = mysql_query("select * from `download` where type = 'file' and id = '" . $file . "';");
$file2 = mysql_num_rows($file1);
$adrfile = mysql_fetch_array($file1);

if (($file1 == 0) || (!is_file("$adrfile[adres]/$adrfile[name]"))) {
    echo "Ошибка при выборе файла<br/><a href='?'>К категориям</a><br/>";
    require_once ('../incfiles/end.php');
    exit;
}
$_SESSION['downl'] = rand(1000, 9999);
$siz = filesize("$adrfile[adres]/$adrfile[name]");
$siz = round($siz / 1024, 2);
$filtime = filemtime("$adrfile[adres]/$adrfile[name]");
$filtime = date("d.m.Y", $filtime);
echo "Файл: $adrfile[name]<br/>Вес:$siz кб<br/>Загружен:$filtime<br/>";
$graf = array("gif", "jpg", "png");
$prg = strtolower(format($adrfile['name']));
if (in_array($prg, $graf)) {
    $sizsf = GetImageSize("$adrfile[adres]/$adrfile[name]");
    $widthf = $sizsf[0];
    $heightf = $sizsf[1];
    echo "Размеры $widthf*$heightf px<br/>";
    #  !предпросмотр!
    $namefile = $adrfile['name'];
    $infile = "$adrfile[adres]/$namefile";

    if (!empty ($_SESSION['razm'])) {
        $razm = $_SESSION['razm'];
    }
    else {
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
    switch ($prg) {
        case "gif" :
            $im = ImageCreateFromGIF($infile);
            break;
        case "jpg" :
            $im = ImageCreateFromJPEG($infile);
            break;
        case "jpeg" :
            $im = ImageCreateFromJPEG($infile);
            break;
        case "png" :
            $im = ImageCreateFromPNG($infile);
            break;
    }
    $im1 = ImageCreateTrueColor($tn_width, $tn_height);
    imagecopyresized($im1, $im, 0, 0, 0, 0, $tn_width, $tn_height, $width, $height);
    $path = "$filesroot/graftemp";
    switch ($prg) {
        case "gif" :
            $imagnam = "$path/$namefile.temp.gif";
            ImageGif($im1, $imagnam, $quality);
            echo "<img src='" . $imagnam . "' alt=''/><br/>";
            break;
        case "jpg" :
            $imagnam = "$path/$namefile.temp.jpg";
            imageJpeg($im1, $imagnam, $quality);
            echo "<img src='" . $imagnam . "' alt=''/><br/>";
            break;
        case "jpeg" :
            $imagnam = "$path/$namefile.temp.jpg";
            imageJpeg($im1, $imagnam, $quality);
            echo "<img src='" . $imagnam . "' alt=''/><br/>";

            break;
        case "png" :
            $imagnam = "$path/$namefile.temp.png";
            imagePng($im1, $imagnam, $quality);
            echo "<img src='" . $imagnam . "' alt=''/><br/>";
            break;
    }
    imagedestroy($im);
    imagedestroy($im1);
    @ chmod("$imagnam", 0644);
}
if ($prg == "mp3") {
    $id3 = new MP3_Id();
    $result = $id3->read("$adrfile[adres]/$adrfile[name]");
    $result = $id3->study();

    echo '<p>';
    echo 'Исполнитель: <b>' . $id3->artists . '</b><br />';
    echo 'Альбом: <b>' . $id3->album . '</b><br />';
    echo 'Год выхода: <b>' . $id3->year . '</b><br />';
    echo 'Композиция: <b>' . $id3->name . '</b>';
    echo '</p>';

    echo "Каналы:" . $id3->getTag('mode') . "<br/>";
    if ($id3->getTag('bitrate') != 0) {
        echo "Битрейт: " . $id3->getTag('bitrate') . " кбит/сек<br/>
Длительность: " . $id3->getTag('length') . "<br/>";
    }
    else {
        echo "Не удалось распознать кодек<br/>";
    }
}
if (empty ($adrfile['text'])) {
    echo "<p>Описание отсутствует</p>";
}
else {
    echo "<p>Описание:<br/>$adrfile[text]</p>";
}

if (!empty ($adrfile['ip'])) {
    echo "Скачиваний: $adrfile[ip]<br/>";
}

if (!empty ($adrfile['soft'])) {
    $rating = explode(",", $adrfile['soft']);

    $rat = $rating[0] / $rating[1];
    $rat = round($rat, 2);
    echo "Средний рейтинг: $rat<br/>Всего оценило: $rating[1] человек<br/>";
}

echo "Оценить:<br/><form action='index.php?act=rat&amp;id=" . $file . "' method='post'><select name='rat'>";
for ($i = 10; $i >= 1;--$i) {
    echo "<option>$i</option>";
}
echo "</select><input type='submit' value='Ok!'/></form><br/>";
if ((!in_array($prg, $graf)) && ($prg != "mp3")) {
    if (empty ($adrfile['screen'])) {
        echo "Скриншот отсутствует<br/>";
    }
    else {
        echo "Скриншот<br/>";
        $infile = "$screenroot/$adrfile[screen]";

        if (!empty ($_SESSION['razm'])) {
            $razm = $_SESSION['razm'];
        }
        else {
            $razm = 50;
        }
        $sizs = GetImageSize($infile);
        $width = $sizs[0];
        $height = $sizs[1];
        $quality = 100;
        $angle = 0;
        $fontsiz = 20;
        $tekst = $copyright;
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
        $format = format($infile);
        switch ($format) {
            case "gif" :
                $im = ImageCreateFromGIF($infile);
                break;
            case "jpg" :
                $im = ImageCreateFromJPEG($infile);
                break;
            case "jpeg" :
                $im = ImageCreateFromJPEG($infile);
                break;
            case "png" :
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
        imagettftext($im, $font_size, $angle, '10', $height - 10, $color, $fontus, $tekst);

        $im1 = imagecreatetruecolor($tn_width, $tn_height);
        $namefile = "$adrfile[name]";

        imagecopyresized($im1, $im, 0, 0, 0, 0, $tn_width, $tn_height, $width, $height);
        $path = "$filesroot/graftemp";
        switch ($format) {
            case "gif" :
                $imagnam = "$path/$namefile.temp.gif";
                ImageGif($im1, $imagnam, $quality);
                echo "<img src='" . $imagnam . "' alt=''/><br/>";
                break;
            case "jpg" :
                $imagnam = "$path/$namefile.temp.jpg";
                imageJpeg($im1, $imagnam, $quality);
                echo "<img src='" . $imagnam . "' alt=''/><br/>";
                break;
            case "jpeg" :
                $imagnam = "$path/$namefile.temp.jpg";
                imageJpeg($im1, $imagnam, $quality);
                echo "<img src='" . $imagnam . "' alt=''/><br/>";

                break;
            case "png" :
                $imagnam = "$path/$namefile.temp.png";
                imagePng($im1, $imagnam, $quality);
                echo "<img src='" . $imagnam . "' alt=''/><br/>";
                break;
        }
        imagedestroy($im);
        imagedestroy($im1);
    }
}
if (($rights == 4 || $rights >= 6) && (!empty ($_GET['file']))) {
    echo "<hr/>";
    if ((!in_array($prg, $graf)) && ($prg != "mp3")) {
        echo "<a href='?act=screen&amp;file=" . $file . "'>Скриншот</a><br/>";
    }
    echo "<a href='?act=opis&amp;file=" . $file . "'>Описание</a><br/>";
    echo "<a href='?act=renf&amp;file=" . $file . "'>Переименовать файл</a><br/>";
    echo "<a href='?act=dfile&amp;file=" . $file . "'>Удалить файл</a><hr/>";
}
if ($prg == "mp3") {
    echo "<a href='?act=cut&amp;id=" . $file . "'>Нарезать</a><br/>";
}
if ($prg == "zip") {
    echo "<a href='?act=zip&amp;file=" . $file . "'>Открыть архив</a><br/>";
}
if ($set['mod_down_comm'] || $rights >= 7) {
    $totalkomm = mysql_result(mysql_query("SELECT COUNT(*) FROM `download` WHERE `type` = 'komm' AND `refid` = '$file'"), 0);
    echo "<a href='?act=down&amp;id=" . $file . "'>Скачать</a><br/><a href='?act=komm&amp;id=" . $file . "'>Комментарии ($totalkomm)</a><br/>";
}
$dnam = mysql_query("select * from `download` where type = 'cat' and id = '" . $adrfile['refid'] . "';");
$dnam1 = mysql_fetch_array($dnam);
$dirname = "$dnam1[text]";
$dirid = "$dnam1[id]";
$nadir = $adrfile['refid'];
while ($nadir != "" && $nadir != "0") {
    echo "&#187;<a href='?cat=" . $nadir . "'>$dirname</a><br/>";
    $dnamm = mysql_query("select * from `download` where type = 'cat' and id = '" . $nadir . "';");
    $dnamm1 = mysql_fetch_array($dnamm);
    $dnamm2 = mysql_query("select * from `download` where type = 'cat' and id = '" . $dnamm1['refid'] . "';");
    $dnamm3 = mysql_fetch_array($dnamm2);
    $nadir = $dnamm1['refid'];
    $dirname = $dnamm3['text'];
}
echo "&#187;<a href='?'>В загрузки</a><br/>";

?>