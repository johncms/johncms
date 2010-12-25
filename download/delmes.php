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

if ($rights == 4 || $rights >= 6) {
    if ($_GET['id'] == "") {
        require_once ("../incfiles/head.php");
        echo "ERROR<br/><a href='index.php?'>Back</a><br/>";
        require_once ('../incfiles/end.php');
        exit;
    }
    $id = intval(trim($_GET['id']));
    $typ = mysql_query("select * from `download` where id='" . $id . "';");
    $ms = mysql_fetch_array($typ);
    if ($ms[type] != "komm") {
        require_once ("../incfiles/head.php");
        echo "ERROR<br/><a href='index.php?'>Back</a><br/>";
        require_once ('../incfiles/end.php');
        exit;
    }
    mysql_query("delete from `download` where `id`='" . $id . "'");
    header("location: index.php?act=komm&id=$ms[refid]");
}

?>