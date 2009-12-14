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

if ($rights >= 6) {
    if ($_GET['id'] == "") {
        echo "Ошибка<br/><a href='index.php'>В галерею</a><br/>";
        require_once ('../incfiles/end.php');
        exit;
    }
    $id = intval($_GET['id']);
    $typ = mysql_query("select * from `gallery` where id='" . $id . "';");
    $ms = mysql_fetch_array($typ);
    if ($ms['type'] != "km") {
        echo "Ошибка<br/><a href='index.php'>В галерею</a><br/>";
        require_once ('../incfiles/end.php');
        exit;
    }
    mysql_query("delete from `gallery` where `id`='" . $id . "';");
    header("location: index.php?act=komm&id=$ms[refid]");
}
else {
    echo "Нет доступа!<br/><a href='index.php'>В галерею</a><br/>";
}

?>