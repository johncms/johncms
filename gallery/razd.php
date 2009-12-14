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
    if (isset ($_POST['submit'])) {
        $user = intval($_POST['user']);
        $text = check($_POST['text']);
        mysql_query("insert into `gallery` values(0,'0','" . $realtime . "','rz','','" . $text . "','','" . $user . "','','');");
        header("location: index.php");
    }
    else {
        echo
        "Добавление раздела.<br/><form action='index.php?act=razd' method='post'>Введите название:<br/><input type='text' name='text'/><br/><input type='checkbox' name='user' value='1'/>Для альбомов юзеров<br/><input type='submit' name='submit' value='Ok!'/></form><br/><a href='index.php'>В галерею</a><br/>";
    }
}
else {
    header("location: index.php");
}

?>