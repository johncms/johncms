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
    if (empty ($_GET['id'])) {
        echo "Ошибка!<br/><a href='index.php'>В галерею</a><br/>";
        require_once ("../incfiles/end.php");
        exit;
    }
    $id = intval($_GET['id']);
    $type = mysql_query("select * from `gallery` where id='" . $id . "';");
    $ms = mysql_fetch_array($type);
    if ($ms['type'] != "rz") {
        echo "Ошибка!<br/><a href='index.php'>В галерею</a><br/>";
        require_once ("../incfiles/end.php");
        exit;
    }

    if (isset ($_POST['submit'])) {
        $text = check($_POST['text']);
        mysql_query("insert into `gallery` values(0,'" . $id . "','" . $realtime . "','al','','" . $text . "','','','','');");
        header("location: index.php?id=$id");
    }
    else {
        echo "Добавление альбома в раздел $ms[text].<br/><form action='index.php?act=cral&amp;id=" . $id .
        "' method='post'>Введите название:<br/><input type='text' name='text'/><br/><input type='submit' name='submit' value='Ok!'/></form><br/><a href='index.php?id=" . $id . "'>В раздел</a><br/>";
    }
}
else {
    header("location: index.php");
}

?>