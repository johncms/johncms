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


$delarc = opendir($filesroot . '/arctemp');
$mp = array();
while ($zp = readdir($delarc)) {
    if ($zp != '.' && $zp != '..' && $zp != 'index.php') {
        $mp[] = $zp;
    }
}
closedir($delarc);
$totalmp = count($mp);
for ($imp = 0; $imp < $totalmp; $imp++) {
    $filtime[$imp] = filemtime($filesroot . '/arctemp/' . $mp[$imp]);
    $tim = time();
    $ftime1 = $tim - 300;
    if ($filtime[$imp] < $ftime1) {
        unlink($filesroot . '/arctemp/' . $mp[$imp]);
    }
}
$error = true;
if ($file) {
    $stmt = $db->query('SELECT * FROM `download` WHERE `type` = "file" AND `id` = "' . $file . '" LIMIT 1');
    if ($stmt->rowCount()) {
        $adrfile = $stmt->fetch();
        if (is_file($adrfile['adres'] . '/' . $adrfile['name'])) {
            $error = false;
        }
    }
}
if ($error) {
    echo "ERROR!<br/><a href='?'>" . $lng['back'] . "</a><br/>";
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
        $zfilesize = strstr($listcontent, '--size');
        $zfilesize = str_replace('--size:', '', $zfilesize);
        $zfilesize = str_replace($zfilesize, $zfilesize . '|', $zfilesize);
        $sizelist .= $zfilesize;

        $zfile = strstr($listcontent, "--filename");
        $zfile = str_replace('--filename:', '', $zfile);
        $zfile = str_replace($zfile, $zfile . '|', $zfile);
        $savelist .= $zfile;
    }
}
$sizefiles2 = explode('|', $sizelist);
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
$start = $page * $kmess - $kmess;
if ($count < $start + $kmess) {
    $end = $count;
} else {
    $end = $start + $kmess;
}
for ($i = $start; $i < $end; $i++) {
    $sizefiles = explode("|", $sizelist);
    $selectfile = explode("|", $savelist);
    $path = $selectfile[$i];
    $fname = preg_replace('/.*[\\/]/', '', $path);
    $zdir = preg_replace('/[\\/]?[^\\/]*$/', '', $path);
    $tfl = strtolower(functions::format($fname));
    $df = array("asp", "aspx", "shtml", "htd", "php", "php3", "php4", "php5", "phtml", "htt", "cfm", "tpl", "dtd", "hta", "pl", "js", "jsp");
    if (in_array($tfl, $df)) {
        echo "$zdir/$fname";
    }
    else {
        echo $zdir . '/<a href="index.php?act=arc&amp;file=' . $file . '&amp;f=' . $i . '&amp;start=' . $start . '">' . $fname . '</a>';
    }
    if ($sizefiles[$i] != "0") {
        $sizekb = round($sizefiles[$i] / 1024, 2);
        echo " ($sizekb KB)";
    }

    echo '<br/>';
}
if ($count > $kmess) {
    echo "<hr/>";
    echo functions::display_pagination('index.php?act=zip&amp;file=' . $file . '&amp;', $start, $total, $kmess);
    echo "<form action='index.php'>Перейти к странице:<br/><input type='hidden' name='act' value='zip'/><input type='hidden' name='file' value='" . $file .
        "'/><input type='text' name='page' title='Введите номер страницы'/><br/><input type='submit' title='Нажмите для перехода' value='Go!'/></form>";
}

echo '<br/><br/><a href="?act=view&amp;file=' . $file . '">К файлу</a><br/>';