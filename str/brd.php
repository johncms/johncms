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

define('_IN_JOHNCMS', 1);

$headmod = 'birth';
$textl = 'Именинники';
require_once ("../incfiles/core.php");
require_once ("../incfiles/head.php");

$page = $_GET['page'];
if ($page <= 0) {
    $page = 1;
}
$q = mysql_query("select * from `users` where `dayb`='" . $day . "' and `monthb`='" . $mon . "' and `preg`='1';");
$count = mysql_num_rows($q);
if (empty ($_GET['page'])) {
    $page = 1;
}
else {
    $page = intval($_GET['page']);
}
$start = $page * 10 - 10;
if ($count < $start + 10) {
    $end = $count;
}
else {
    $end = $start + 10;
}
while ($arr = mysql_fetch_array($q)) {
    if ($i >= $start && $i < $end) {
        if ($arr[sex] == "m") {
            $pol = "<img src='../images/m.gif' alt=''/>";
        }
        elseif ($arr[sex] == "zh") {
            $pol = "<img src='../images/f.gif' alt=''/>";
        }

        if (empty ($_SESSION['uid']) || $_SESSION['uid'] == $arr[id]) {
            print "$pol <b>$arr[name]</b>";
        }
        else {
            print "$pol <a href='pradd.php?adr=" . $arr[id] . "&amp;act=write&amp;bir'>$arr[name]</a>";
        }
        switch ($arr[rights]) {
            case 7 :
                echo ' Adm ';
                break;
            case 6 :
                echo ' Smd ';
                break;
            case 5 :
                echo ' Mod ';
                break;
            case 4 :
                echo ' Mod ';
                break;
            case 3 :
                echo ' Mod ';
                break;
            case 2 :
                echo ' Mod ';
                break;
            case 1 :
                echo ' Kil ';
                break;
        }

        $ontime = $arr[lastdate];
        $ontime2 = $ontime + 300;
        if ($realtime > $ontime2) {
            echo " [Off]<br/>";
        }
        else {
            echo " [ON]<br/>";
        }

    }
    ++$i;
}
if ($count > 10)    //TODO: Переделать на новый листинг по страницам

    {
    echo "<hr/>";
    $ba = ceil($count / 10);
    echo "Страницы:<br/>";
    $asd = $start - (10);
    $asd2 = $start + (10 * 2);
    if ($start != 0) {
        echo '<a href="brd.php?page=' . ($page - 1) . '">&lt;&lt;</a> ';
    }
    if ($asd < $count && $asd > 0) {
        echo ' <a href="brd.php?page=1&amp;">1</a> .. ';
    }
    $page2 = $ba - $page;
    $pa = ceil($page / 2);
    $paa = ceil($page / 3);
    $pa2 = $page + floor($page2 / 2);
    $paa2 = $page + floor($page2 / 3);
    $paa3 = $page + (floor($page2 / 3) * 2);
    if ($page > 13) {
        echo ' <a href="pbrd.php?page=' . $paa . '">' . $paa . '</a> <a href="brd.php?page=' . ($paa + 1) . '">' . ($paa + 1) . '</a> .. <a href="brd.php?page=' . ($paa * 2) . '">' . ($paa * 2) . '</a> <a href="brd.php?page=' . ($paa * 2 +
        1) . '">' . ($paa * 2 + 1) . '</a> .. ';
    }
    elseif ($page > 7) {
        echo ' <a href="brd.php?page=' . $pa . '">' . $pa . '</a> <a href="brd.php?page=' . ($pa + 1) . '">' . ($pa + 1) . '</a> .. ';
    }
    for ($i = $asd; $i < $asd2;) {
        if ($i < $count && $i >= 0) {
            $ii = floor(1 + $i / 10);

            if ($start == $i) {
                echo " <b>$ii</b>";
            }
            else {
                echo ' <a href="brd.php?page=' . $ii . '">' . $ii . '</a> ';
            }
        }
        $i = $i + 10;
    }
    if ($page2 > 12) {
        echo ' .. <a href="brd.php?page=' . $paa2 . '">' . $paa2 . '</a> <a href="brd.php?page=' . ($paa2 + 1) . '">' . ($paa2 + 1) . '</a> .. <a href="brd.php?page=' . ($paa3) . '">' . ($paa3) . '</a> <a href="brd.php?page=' . ($paa3 + 1)
        . '">' . ($paa3 + 1) . '</a> ';
    }
    elseif ($page2 > 6) {
        echo ' .. <a href="brd.php?page=' . $pa2 . '">' . $pa2 . '</a> <a href="brd.php?page=' . ($pa2 + 1) . '">' . ($pa2 + 1) . '</a> ';
    }
    if ($asd2 < $count) {
        echo ' .. <a href="brd.php?page=' . $ba . '">' . $ba . '</a>';
    }
    if ($count > $start + 10) {
        echo ' <a href="brd.php?page=' . ($page + 1) . '">&gt;&gt;</a>';
    }
    echo
    "<form action='brd.php'>Перейти к странице:<br/><input type='text' name='page' title='Введите номер страницы'/><br/><input type='submit' title='Нажмите для перехода' value='Go!'/></form>";
}

echo "<hr/>Всего именинников сегодня: $count<br/>";

require_once ("../incfiles/end.php");

?>