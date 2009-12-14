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

defined('_IN_JOHNCMS') or die('Error: restricted access');

// Рейтинг самых читаемых статей
echo "<p><b>50 самых читаемых статей</b></p><hr/>";
$req = mysql_query("select * from `lib` where `type` = 'bk' and `moder`='1' and `count`>'0' ORDER BY `count` DESC LIMIT 50;");
$totalnew = mysql_num_rows($req);
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
    while ($res = mysql_fetch_array($req)) {
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
            $vr = $newf['time'] + $set_user['sdvig'] * 3600;
            $vr = date("d.m.y / H:i", $vr);
            echo $div;
            echo '<b><a href="?id=' . $res['id'] . '">' . htmlentities($res['name'], ENT_QUOTES, 'UTF-8') . '</a></b><br/>';
            echo htmlentities($res['announce'], ENT_QUOTES, 'UTF-8') . '<br />';
            echo 'Прочтений: ' . $res['count'] . '<br/>';
            $nadir = $res['refid'];
            $dirlink = $nadir;
            $pat = "";
            while ($nadir != "0") {
                $dnew = mysql_query("select * from `lib` where type = 'cat' and id = '" . $nadir . "';");
                $dnew1 = mysql_fetch_array($dnew);
                $pat = $dnew1['text'] . '/' . $pat;
                $nadir = $dnew1['refid'];
            }
            $l = mb_strlen($pat);
            $pat1 = mb_substr($pat, 0, $l - 1);
            echo '[<a href="index.php?id=' . $dirlink . '">' . $pat1 . '</a>]</div>';
        }
        ++$i;
    }
    echo "<hr/><p>";
    if ($totalnew > 10)        //TODO: Переделать на новый листинг по страницам

        {
        $ba = ceil($totalnew / 10);
        echo "Страницы:<br/>";
        if ($start != 0) {
            echo '<a href="index.php?act=topread&amp;page=' . ($page - 1) . '">&lt;&lt;</a> ';
        }
        $asd = $start - 10;
        $asd2 = $start + 20;
        if ($asd < $totalnew && $asd > 0) {
            echo ' <a href="index.php?act=topread&amp;page=1">1</a> .. ';
        }
        $page2 = $ba - $page;
        $pa = ceil($page / 2);
        $paa = ceil($page / 3);
        $pa2 = $page + floor($page2 / 2);
        $paa2 = $page + floor($page2 / 3);
        $paa3 = $page + (floor($page2 / 3) * 2);
        if ($page > 13) {
            echo ' <a href="index.php?act=topread&amp;page=' . $paa . '">' . $paa . '</a> <a href="index.php?act=topread&amp;page=' . ($paa + 1) . '">' . ($paa + 1) . '</a> .. <a href="index.php?act=topread&amp;page=' . ($paa * 2) . '">' .
            ($paa * 2) . '</a> <a href="index.php?act=topread&amp;page=' . ($paa * 2 + 1) . '">' . ($paa * 2 + 1) . '</a> .. ';
        }
        elseif ($page > 7) {
            echo ' <a href="index.php?act=topread&amp;page=' . $pa . '">' . $pa . '</a> <a href="index.php?act=topread&amp;page=' . ($pa + 1) . '">' . ($pa + 1) . '</a> .. ';
        }
        for ($i = $asd; $i < $asd2;) {
            if ($i < $totalnew && $i >= 0) {
                $ii = floor(1 + $i / 10);
                if ($start == $i) {
                    echo " <b>$ii</b>";
                }
                else {
                    echo ' <a href="index.php?act=topread&amp;page=' . $ii . '">' . $ii . '</a> ';
                }
            }
            $i = $i + 10;
        }
        if ($page2 > 12) {
            echo ' .. <a href="index.php?act=topread&amp;page=' . $paa2 . '">' . $paa2 . '</a> <a href="index.php?act=topread&amp;page=' . ($paa2 + 1) . '">' . ($paa2 + 1) . '</a> .. <a href="index.php?act=topread&amp;page=' . ($paa3) .
            '">' . ($paa3) . '</a> <a href="index.php?act=topread&amp;page=' . ($paa3 + 1) . '">' . ($paa3 + 1) . '</a> ';
        }
        elseif ($page2 > 6) {
            echo ' .. <a href="index.php?act=topread&amp;page=' . $pa2 . '">' . $pa2 . '</a> <a href="?act=topread&amp;page=' . ($pa2 + 1) . '">' . ($pa2 + 1) . '</a> ';
        }
        if ($asd2 < $totalnew) {
            echo ' .. <a href="index.php?act=topread&amp;page=' . $ba . '">' . $ba . '</a>';
        }
        if ($totalnew > $start + 10) {
            echo ' <a href="index.php?act=topread&amp;page=' . ($page + 1) . '">&gt;&gt;</a>';
        }
        echo
        "<form action='index.php'>Перейти к странице:<br/><input type='hidden' name='act' value='new'/><input type='text' name='page' title='Введите номер страницы'/><br/><input type='submit' title='Нажмите для перехода' value='Go!'/></form>";
    }
}
else {
    echo "<p>Еще никто не читал в библиотеке<br/>";
}
echo "<a href='index.php?'>В библиотеку</a></p>";

?>