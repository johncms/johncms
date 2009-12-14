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
    if ($ms['type'] != "ft") {
        echo "Ошибка<br/><a href='index.php'>В галерею</a><br/>";
        require_once ('../incfiles/end.php');
        exit;
    }
    if (isset ($_GET['yes'])) {
        $km = mysql_query("select * from `gallery` where type='km' and refid='" . $id . "';");
        while ($km1 = mysql_fetch_array($km)) {
            mysql_query("delete from `gallery` where `id`='" . $km1['id'] . "';");
        }
        unlink("foto/$ms[name]");
        mysql_query("delete from `gallery` where `id`='" . $id . "';");
        header("location: index.php?id=$ms[refid]");
    }
    else {
        echo "Вы уверены?<br/>";
        echo "<a href='index.php?act=delf&amp;id=" . $id . "&amp;yes'>Да</a> | <a href='index.php?id=" . $ms['refid'] . "'>Нет</a><br/>";
    }
}
else {
    header("location: index.php");
}

?>