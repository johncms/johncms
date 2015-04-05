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

if ($_GET['file'] == "") {
    require_once ('../incfiles/head.php');
    echo functions::display_error($lng_dl['file_not_selected'], '<a href="index.php">' . $lng['back'] . '</a>');
    require_once ('../incfiles/end.php');
    exit;
}
if ($_GET['f'] == "") {
    require_once ('../incfiles/head.php');
    echo "Не выбран файл из архива<br/><a href='?act=zip&amp;file=" . $file . "'>В архив</a><br/>";
    require_once ('../incfiles/end.php');
    exit;
}
$file = intval(trim($_GET['file']));
$file1 = mysql_query("select * from `download` where `type` = 'file' and `id` = '" . $file . "'");
$file2 = mysql_num_rows($file1);
$adrfile = mysql_fetch_array($file1);
if (($file2 == 0) || (!is_file($adrfile['adres'] . '/' . $adrfile['name']))) {
    require_once ("../incfiles/head.php");
    echo "Ошибка при выборе файла<br/><a href='?'>К категориям</a><br/>";
    require_once ('../incfiles/end.php');
    exit;
}
$zip = new PclZip($adrfile['adres'] . '/' . $adrfile['name']);

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
$sizefiles = explode("|", $sizelist);
$selectfile = explode("|", $savelist);
$f = $_GET['f'];
$path = $selectfile[$f];
$fname = ereg_replace(".*[\\/]", "", $path);
$zdir = ereg_replace("[\\/]?[^\\/]*$", "", $path);
$tfl = strtolower(functions::format($fname));
$df = array("asp", "aspx", "shtml", "htd", "php", "php3", "php4", "php5", "phtml", "htt", "cfm", "tpl", "dtd", "hta", "pl", "js", "jsp");
if (!in_array($tfl, $df)) {
    $content = $zip->extract(PCLZIP_OPT_BY_NAME, $path, PCLZIP_OPT_EXTRACT_AS_STRING);
    $content1 = $zip->extract(PCLZIP_OPT_BY_NAME, $open, PCLZIP_OPT_EXTRACT_IN_OUTPUT);
    $content = $content[0]['content'];
    $FileName = "$filesroot/arctemp/$fname";
    $fid = @ fopen($FileName, "wb");
    if ($fid) {
        if (flock($fid, LOCK_EX)) {
            fwrite($fid, $content);
            flock($fid, LOCK_UN);
        }
        fclose($fid);
    }
    if (is_file("$filesroot/arctemp/$fname")) {
        header("location: $filesroot/arctemp/$fname");
    }
}