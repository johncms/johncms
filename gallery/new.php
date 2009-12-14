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

echo '<div class="phdr">Новые фотографии</div>';
$old = $realtime - (3 * 24 * 3600);

$newfile = mysql_query("select * from `gallery` where time > '" . $old . "' and type='ft' order by time desc;");
$totalnew = mysql_num_rows($newfile);
if (empty ($_GET['page'])) {
    $page = 1;
}
else {
    $page = intval($_GET['page']);
}
$start = $page * 10 - 10;
if ($totalnew < $start + 10) {
    $end = $totalnew;
}
else {
    $end = $start + 10;
}
if ($totalnew != 0) {
    while ($newf = mysql_fetch_array($newfile)) {
        if ($i >= $start && $i < $end) {
            $d = $i / 2;
            $d1 = ceil($d);
            $d2 = $d1 - $d;
            $d3 = ceil($d2);
            if ($d3 == 0) {
                $div = "<div class='c'>";
            }
            else {
                $div = "<div class='b'>";
            }
            echo "$div<br/>&nbsp;<a href='index.php?id=" . $newf['id'] . "'>";
            $infile = "foto/$newf[name]";
            if (!empty ($_SESSION['frazm'])) {
                $razm = $_SESSION['frazm'];
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
            $im1 = imagecreatetruecolor($tn_width, $tn_height);
            $namefile = "$newf[name]";
            imagecopyresized($im1, $im, 0, 0, 0, 0, $tn_width, $tn_height, $width, $height);
            switch ($format) {
                case "gif" :
                    $imagnam = "temp/$namefile.temp.gif";
                    ImageGif($im1, $imagnam, $quality);
                    echo "<img src='" . $imagnam . "' alt=''/><br/>";
                    break;
                case "jpg" :
                    $imagnam = "temp/$namefile.temp.jpg";
                    imageJpeg($im1, $imagnam, $quality);
                    echo "<img src='" . $imagnam . "' alt=''/><br/>";
                    break;
                case "jpeg" :
                    $imagnam = "temp/$namefile.temp.jpg";
                    imageJpeg($im1, $imagnam, $quality);
                    echo "<img src='" . $imagnam . "' alt=''/><br/>";

                    break;
                case "png" :
                    $imagnam = "temp/$namefile.temp.png";
                    imagePng($im1, $imagnam, $quality);
                    echo "<img src='" . $imagnam . "' alt=''/><br/>";

                    break;
            }
            imagedestroy($im);
            imagedestroy($im1);
            $vrf = $newf[time] + $set_user['sdvig'] * 3600;
            $vrf1 = date("d.m.y / H:i", $vrf);
            $kom = mysql_query("select * from `gallery` where type='km' and refid='" . $newf['id'] . "';");
            $kom1 = mysql_num_rows($kom);
            echo "</a><br/>Добавлено: $vrf1<br/>Подпись: $newf[text]<br/><a href='index.php?act=komm&amp;id=" . $newf['id'] . "'>Комментарии</a> ($kom1)<br/>";
            $al = mysql_query("select * from `gallery` where type = 'al' and id = '" . $newf['refid'] . "';");
            $al1 = mysql_fetch_array($al);
            $rz = mysql_query("select * from `gallery` where type = 'rz' and id = '" . $al1['refid'] . "';");
            $rz1 = mysql_fetch_array($rz);
            echo '<a href="index.php?id=' . $al1['id'] . '">' . $rz1['text'] . '&nbsp;/&nbsp;' . $al1['text'] . '</a></div>';
        }
        ++$i;
    }
    if ($totalnew > 10)        //TODO: Переделать на новый листинг по страницам

        {
        echo "<hr/>";
        $ba = ceil($totalnew / 10);
        echo "Страницы:<br/>";
        if ($start != 0) {
            echo '<a href="index.php?act=new&amp;page=' . ($page - 1) . '">&lt;&lt;</a> ';
        }
        $asd = $start - 10;
        $asd2 = $start + 20;
        if ($asd < $totalnew && $asd > 0) {
            echo ' <a href="index.php?act=new&amp;page=1">1</a> .. ';
        }
        $page2 = $ba - $page;
        $pa = ceil($page / 2);
        $paa = ceil($page / 3);
        $pa2 = $page + floor($page2 / 2);
        $paa2 = $page + floor($page2 / 3);
        $paa3 = $page + (floor($page2 / 3) * 2);
        if ($page > 13) {
            echo ' <a href="index.php?act=new&amp;page=' . $paa . '">' . $paa . '</a> <a href="index.php?act=new&amp;page=' . ($paa + 1) . '">' . ($paa + 1) . '</a> .. <a href="index.php?act=new&amp;page=' . ($paa * 2) . '">' . ($paa * 2) .
            '</a> <a href="index.php?act=new&amp;page=' . ($paa * 2 + 1) . '">' . ($paa * 2 + 1) . '</a> .. ';
        }
        elseif ($page > 7) {
            echo ' <a href="index.php?act=new&amp;page=' . $pa . '">' . $pa . '</a> <a href="index.php?act=new&amp;page=' . ($pa + 1) . '">' . ($pa + 1) . '</a> .. ';
        }
        for ($i = $asd; $i < $asd2;) {
            if ($i < $totalnew && $i >= 0) {
                $ii = floor(1 + $i / 10);

                if ($start == $i) {
                    echo " <b>$ii</b>";
                }
                else {
                    echo ' <a href="index.php?act=new&amp;page=' . $ii . '">' . $ii . '</a> ';
                }
            }
            $i = $i + 10;
        }
        if ($page2 > 12) {
            echo ' .. <a href="index.php?act=new&amp;page=' . $paa2 . '">' . $paa2 . '</a> <a href="index.php?act=new&amp;page=' . ($paa2 + 1) . '">' . ($paa2 + 1) . '</a> .. <a href="index.php?act=new&amp;page=' . ($paa3) . '">' . ($paa3)
            . '</a> <a href="index.php?act=new&amp;page=' . ($paa3 + 1) . '">' . ($paa3 + 1) . '</a> ';
        }
        elseif ($page2 > 6) {
            echo ' .. <a href="index.php?act=new&amp;page=' . $pa2 . '">' . $pa2 . '</a> <a href="?act=new&amp;page=' . ($pa2 + 1) . '">' . ($pa2 + 1) . '</a> ';
        }
        if ($asd2 < $totalnew) {
            echo ' .. <a href="index.php?act=new&amp;page=' . $ba . '">' . $ba . '</a>';
        }
        if ($totalnew > $start + 10) {
            echo ' <a href="index.php?act=new&amp;page=' . ($page + 1) . '">&gt;&gt;</a>';
        }
        echo
        "<form action='index.php'>Перейти к странице:<br/><input type='hidden' name='act' value='new'/><input type='text' name='page' title='Введите номер страницы'/><br/><input type='submit' title='Нажмите для перехода' value='Go!'/></form>";
    }
    echo "<br/>Всего новых фотографий за 3 дня: $totalnew";
}
else {
    echo "<br/>Нет новых фотографий за 3 дня";
}
echo "<br/><a href='index.php?'>В галерею</a><br/>";

?>