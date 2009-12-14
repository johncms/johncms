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
if ($_GET['id'] == "") {
    echo "Ошибка<br/><a href='index.php?'>К категориям</a><br/>";
    require_once ('../incfiles/end.php');
    exit;
}
$id = intval(trim($_GET['id']));
$typ = mysql_query("select * from `download` where id='" . $id . "';");
$ms = mysql_fetch_array($typ);
if ($ms[type] != "file") {
    echo "Ошибка<br/><a href='index.php?'>К категориям</a><br/>";
    require_once ('../incfiles/end.php');
    exit;
}
if ($_SESSION['rat'] == $id) {
    echo "Вы уже оценивали этот файл!<br/><a href='index.php?act=view&amp;file=" . $id . "'>К файлу</a><br/>";
    require_once ('../incfiles/end.php');
    exit;
}
$rat = intval(check($_POST['rat']));
if (!empty ($ms[soft])) {
    $rt = explode(",", $ms[soft]);
    $rt1 = $rt[0] + $rat;
    $rt2 = $rt[1] + 1;
    $rat1 = "$rt1,$rt2";
}
else {
    $rat1 = "$rat,1";
}
$_SESSION['rat'] = $id;
mysql_query("update `download` set soft = '" . $rat1 . "' where id = '" . $id . "';");
echo "Спасибо, Ваша оценка принята!<br/><a href='index.php?act=view&amp;file=" . $id . "'>К файлу</a><br/>";

?>