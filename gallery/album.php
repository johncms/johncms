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

if (!empty ($_SESSION['uid'])) {
    if (empty ($_GET['id'])) {
        echo "Ошибка!";
        require_once ("../incfiles/end.php");
        exit;
    }
    $id = intval($_GET['id']);
    $type = mysql_query("select * from `gallery` where id='" . $id . "';");
    $ms = mysql_fetch_array($type);
    if ($ms[type] != "rz") {
        echo "Ошибка!";
        require_once ("../incfiles/end.php");
        exit;
    }
    mysql_query("insert into `gallery` values(0,'" . $id . "','" . $realtime . "','al','" . $login . "','" . $login . "','','1','','');");
    $al = mysql_insert_id();
    header("location: index.php?id=$al");
}
else {
    header("location: index.php");
}

?>