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

require_once ("../incfiles/head.php");

$delarc = opendir("$filesroot/arctemp");
$mp = array();
while ($zp = readdir($delarc)) {
    if ($zp != "." && $zp != ".." && $zp != "index.php") {
        $mp[] = $zp;
    }
}
closedir($delarc);
$totalmp = count($mp);
for ($imp = 0; $imp < $totalmp; $imp++) {
    $filtime[$imp] = filemtime("$filesroot/arctemp/$mp[$imp]");
    $tim = time();
    $ftime1 = $tim - 300;
    if ($filtime[$imp] < $ftime1) {
        @unlink("$filesroot/arctemp/$mp[$imp]");
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
$zip = new PclZip("$adrfile[adres]/$adrfile[name]");

if (($list = $zip->listContent()) == 0) {
    die("Ошибка: " . $zip->errorInfo(true));
}
for ($i = 0; $i < sizeof($list); $i++) {
    for (reset($list[$i]); $key = key($list[$i]); next($list[$i])) {
        $listcontent = "[$i]--$key:" . $list[$i][$key] . "";
        $zfilesize = strstr($listcontent, "--size");
        $zfilesize = ereg_replace("--size:", "", $zfilesize);
        $zfilesize = @ ereg_replace("$zfilesize", "$zfilesize|", $zfilesize);
        $sizelist .= "$zfilesize";

        $zfile = strstr($listcontent, "--filename");
        $zfile = ereg_replace("--filename:", "", $zfile);
        $zfile = @ ereg_replace("$zfile", "$zfile|", $zfile);
        $savelist .= "$zfile";
    }
}
$sizefiles2 = explode("|", $sizelist);
$sizelist2 = array_sum($sizefiles2);
$obkb = round($sizelist2 / 1024, 2);

$preview = "$savelist";
$preview = explode("|", $preview);

$count = count($preview) - 1;
echo "<b>$arch</b><br/>Всего файлов: $count<br/>Вес распакованного архива: $obkb кб<br/>Вы можете скачать отдельные файлы из этого архива<hr/>";

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
for ($i = $start; $i < $end; $i++) {
    $sizefiles = explode("|", $sizelist);
    $selectfile = explode("|", $savelist);
    $path = $selectfile[$i];
    $fname = ereg_replace(".*[\\/]", "", $path);
    $zdir = ereg_replace("[\\/]?[^\\/]*$", "", $path);
    $tfl = strtolower(functions::format($fname));
    $df = array("asp", "aspx", "shtml", "htd", "php", "php3", "php4", "php5", "phtml", "htt", "cfm", "tpl", "dtd", "hta", "pl", "js", "jsp");
    if (in_array($tfl, $df)) {
        echo "$zdir/$fname";
    }
    else {
        echo $zdir . '/<a href="' . $_SERVER['PHP_SELF'] . '?act=arc&amp;file=' . $file . '&amp;f=' . $i . '&amp;start=' . $start . '">' . $fname . '</a>';
    }
    if ($sizefiles[$i] != "0") {
        $sizekb = round($sizefiles[$i] / 1024, 2);
        echo " ($sizekb кб)";
    }

    echo '<br/>';
}
if ($count > 10) {
    echo "<hr/>";
    $ba = ceil($count / 10);
    echo "Страницы:<br/>";    //TODO: Переделать на новый листинг по страницам
    if ($start != 0) {
        echo '<a href="index.php?act=zip&amp;file=' . $file . '&amp;page=' . ($page - 1) . '">&lt;&lt;</a> ';
    }
    $asd = $start - 10;
    $asd2 = $start + 20;
    if ($asd < $count && $asd > 0) {
        echo ' <a href="index.php?act=zip&amp;file=' . $file . '&amp;page=1">1</a> .. ';
    }
    $page2 = $ba - $page;
    $pa = ceil($page / 2);
    $paa = ceil($page / 3);
    $pa2 = $page + floor($page2 / 2);
    $paa2 = $page + floor($page2 / 3);
    $paa3 = $page + (floor($page2 / 3) * 2);
    if ($page > 13) {
        echo ' <a href="index.php?act=zip&amp;file=' . $file . '&amp;page=' . $paa . '">' . $paa . '</a> <a href="index.php?act=zip&amp;file=' . $file . '&amp;page=' . ($paa + 1) . '">' . ($paa + 1) .
            '</a> .. <a href="index.php?act=zip&amp;file=' . $file . '&amp;page=' . ($paa * 2) . '">' . ($paa * 2) . '</a> <a href="index.php?act=zip&amp;file=' . $file . '&amp;page=' . ($paa * 2 + 1) . '">' . ($paa * 2 + 1) . '</a> .. ';
    }
    elseif ($page > 7) {
        echo ' <a href="index.php?act=zip&amp;file=' . $file . '&amp;page=' . $pa . '">' . $pa . '</a> <a href="index.php?act=zip&amp;file=' . $file . '&amp;page=' . ($pa + 1) . '">' . ($pa + 1) . '</a> .. ';
    }
    for ($i = $asd; $i < $asd2;) {
        if ($i < $count && $i >= 0) {
            $ii = floor(1 + $i / 10);

            if ($start == $i) {
                echo " <b>$ii</b>";
            }
            else {
                echo ' <a href="index.php?act=zip&amp;file=' . $file . '&amp;page=' . $ii . '">' . $ii . '</a> ';
            }
        }
        $i = $i + 10;
    }
    if ($page2 > 12) {
        echo ' .. <a href="index.php?act=zip&amp;file=' . $file . '&amp;page=' . $paa2 . '">' . $paa2 . '</a> <a href="index.php?act=zip&amp;file=' . $file . '&amp;page=' . ($paa2 + 1) . '">' . ($paa2 + 1) .
            '</a> .. <a href="index.php?act=zip&amp;file=' . $file . '&amp;page=' . ($paa3) . '">' . ($paa3) . '</a> <a href="index.php?act=zip&amp;file=' . $file . '&amp;page=' . ($paa3 + 1) . '">' . ($paa3 + 1) . '</a> ';
    }
    elseif ($page2 > 6) {
        echo ' .. <a href="index.php?act=zip&amp;file=' . $file . '&amp;page=' . $pa2 . '">' . $pa2 . '</a> <a href="index.php?act=zip&amp;file=' . $file . '&amp;page=' . ($pa2 + 1) . '">' . ($pa2 + 1) . '</a> ';
    }
    if ($asd2 < $count) {
        echo ' .. <a href="index.php?act=zip&amp;file=' . $file . '&amp;page=' . $ba . '">' . $ba . '</a>';
    }
    if ($count > $start + 10) {
        echo ' <a href="index.php?act=zip&amp;file=' . $file . '&amp;page=' . ($page + 1) . '">&gt;&gt;</a>';
    }
    echo "<form action='index.php'>Перейти к странице:<br/><input type='hidden' name='act' value='zip'/><input type='hidden' name='file' value='" . $file .
        "'/><input type='text' name='page' title='Введите номер страницы'/><br/><input type='submit' title='Нажмите для перехода' value='Go!'/></form>";
}

echo '<br/><br/><a href="?act=view&amp;file=' . $file . '">К файлу</a><br/>';