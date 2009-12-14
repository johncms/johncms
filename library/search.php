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

$_SESSION['lib'] = rand(1000, 9999);
if (!empty ($_POST['srh'])) {
    $srh = trim($_POST['srh']);
}
else {
    echo "Вы не ввели условие поиска!<br/><a href='?'>К категориям</a><br/>";
    require_once ('../incfiles/end.php');
    exit;
}
if (mb_strlen($_POST['srh']) < 2) {
    echo "В запросе на поиск должно быть не менее 2-х символов.<br/><a href='?'>К категориям</a><br/>";
    require_once ('../incfiles/end.php');
    exit;
}
$mod = isset ($_POST['mod']) ? intval($_POST['mod']) : 1;

$psk = mysql_query("select * from `lib` where  type='bk' and moder='1';");
$res = array();
while ($array = mysql_fetch_array($psk)) {
    switch ($mod) {
        case 1 :
            if (stristr($array ['name'], $srh)) {
                $arrname = htmlentities($array ['name'], ENT_QUOTES, 'UTF-8');
                $res[] = '<br/><a href="index.php?id=' . $array ['id'] . '">' . $arrname . '</a><br/>';
            }
            break;

        case 2 :
            $pg = mb_strlen($tx);
            if (!empty ($_SESSION['symb'])) {
                $simvol = $_SESSION['symb'];
            }
            else {
                $simvol = 600;
            }
            $page = ceil($pg / $simvol);
            $tx = $array ['text'];
            if (stristr($tx, $srh)) {
                $arrname = htmlentities($array ['name'], ENT_QUOTES, 'UTF-8');
                $tx = htmlentities($tx, ENT_QUOTES, 'UTF-8');
                $a = mb_strpos($tx, $srh);
                $page = ceil($a / $simvol) + 1;
                if ($a > 100) {
                    $a1 = $a - 100;
                    $a2 = 200;
                }
                else {
                    $a1 = 0;
                    $a2 = 100;
                }
                $tx = mb_substr($tx, $a1, $a2);
                $b = mb_strpos($tx, " ");
                $b2 = mb_strrpos($tx, " ");
                $b1 = mb_strlen($tx);
                $tx = mb_substr($tx, $b, $b2 - $b);
                $tx = str_replace($srh1, "<b>$srh1</b>", $tx);
                $tx = "...$tx...";
                $res[] = "<a href='?id=" . $array ['id'] . "&amp;page=" . $page . "'>$arrname</a><br/>$tx<br/>";
            }
            break;

        default :
            header("location: index.php");
            break;
    }
}
$g = count($res);
if ($g == 0) {
    echo "<br/>По вашему запросу ничего не найдено<br/>";
}
else {
    $srh = htmlentities($srh, ENT_QUOTES, 'UTF-8');
    echo "<b>Результаты поиска</b><br/><br/>Условие поиска: <b>$srh</b><br/>Метод поиска: ";

    if ($mod == 1) {
        echo "по названию<hr/>";
    }
    else {
        echo "по тексту<hr/>";
    }
}
if (empty ($_GET['page'])) {
    $page = 1;
}
else {
    $page = intval($_GET['page']);
}
$start = $page * 10 - 10;
if ($g < $start + 10) {
    $end = $g;
}
else {
    $end = $start + 10;
}
for ($i = $start; $i < $end; $i++) {
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
    echo "$div $res[$i]</div>";
}
echo "<hr/>";
if ($g > 10) {
    $ba = ceil($g / 10);
    echo "Страницы:<br/>";
    $asd = $start - 10;
    $asd2 = $start + 20;
    if ($start != 0) {
        echo '<a href="index.php?act=search&amp;mod=' . $mod . '&amp;srh=' . $srh . '&amp;page=' . ($page - 1) . '">&lt;&lt;</a> ';
    }
    if ($asd < $g && $asd > 0) {
        echo ' <a href="index.php?act=search&amp;mod=' . $mod . '&amp;srh=' . $srh . '&amp;page=1&amp;">1</a> .. ';
    }
    $page2 = $ba - $page;
    $pa = ceil($page / 2);
    $paa = ceil($page / 3);
    $pa2 = $page + floor($page2 / 2);
    $paa2 = $page + floor($page2 / 3);
    $paa3 = $page + (floor($page2 / 3) * 2);
    if ($page > 13) {
        echo ' <a href="index.php?act=search&amp;mod=' . $mod . '&amp;srh=' . $srh . '&amp;page=' . $paa . '">' . $paa . '</a> <a href="index.php?act=search&amp;mod=' . $mod . '&amp;srh=' . $srh . '&amp;page=' . ($paa + 1) . '">' . ($paa +
        1) . '</a> .. <a href="index.php?act=search&amp;mod=' . $mod . '&amp;srh=' . $srh . '&amp;page=' . ($paa * 2) . '">' . ($paa * 2) . '</a> <a href="index.php?act=search&amp;mod=' . $mod . '&amp;srh=' . $srh . '&amp;page=' . ($paa * 2
        + 1) . '">' . ($paa * 2 + 1) . '</a> .. ';
    }
    elseif ($page > 7) {
        echo ' <a href="index.php?act=search&amp;mod=' . $mod . '&amp;srh=' . $srh . '&amp;page=' . $pa . '">' . $pa . '</a> <a href="index.php?act=search&amp;mod=' . $mod . '&amp;srh=' . $srh . '&amp;page=' . ($pa + 1) . '">' . ($pa + 1) .
        '</a> .. ';
    }
    for ($i = $asd; $i < $asd2;) {
        if ($i < $g && $i >= 0) {
            $ii = floor(1 + $i / 10);
            if ($start == $i) {
                echo " <b>$ii</b>";
            }
            else {
                echo ' <a href="index.php?act=search&amp;mod=' . $mod . '&amp;srh=' . $srh . '&amp;page=' . $ii . '">' . $ii . '</a> ';
            }
        }
        $i = $i + 10;
    }
    if ($page2 > 12) {
        echo ' .. <a href="index.php?act=search&amp;mod=' . $mod . '&amp;srh=' . $srh . '&amp;page=' . $paa2 . '">' . $paa2 . '</a> <a href="index.php?act=search&amp;mod=' . $mod . '&amp;srh=' . $srh . '&amp;page=' . ($paa2 + 1) . '">' . ($paa2
        + 1) . '</a> .. <a href="index.php?act=search&amp;mod=' . $mod . '&amp;srh=' . $srh . '&amp;page=' . ($paa3) . '">' . ($paa3) . '</a> <a href="index.php?act=search&amp;mod=' . $mod . '&amp;srh=' . $srh . '&amp;page=' . ($paa3 + 1) .
        '">' . ($paa3 + 1) . '</a> ';
    }
    elseif ($page2 > 6) {
        echo ' .. <a href="index.php?act=search&amp;mod=' . $mod . '&amp;srh=' . $srh . '&amp;page=' . $pa2 . '">' . $pa2 . '</a> <a href="index.php?act=search&amp;mod=' . $mod . '&amp;srh=' . $srh . '&amp;page=' . ($pa2 + 1) . '">' . ($pa2
        + 1) . '</a> ';
    }
    if ($asd2 < $g) {
        echo ' .. <a href="index.php?act=search&amp;mod=' . $mod . '&amp;srh=' . $srh . '&amp;page=' . $ba . '">' . $ba . '</a>';
    }
    if ($g > $start + 10) {
        echo ' <a href="index.php?act=search&amp;mod=' . $mod . '&amp;srh=' . $srh . '&amp;page=' . ($page + 1) . '">&gt;&gt;</a>';
    }
    echo "<form action='index.php'>Перейти к странице:<br/><input type='hidden' name='act' value='search'/><input type='hidden' name='srh' value='" . $srh . "'/><input type='hidden' name='mod' value='" . $mod .
    "'/><input type='text' name='page' title='Введите номер страницы'/><br/><input type='submit' title='Нажмите для перехода' value='Go!'/></form>";
}
if ($g != 0) {
    echo "<br/>Найдено совпадений: $g";
}
echo '<br/><a href="?">К категориям</a><br/>';

?>