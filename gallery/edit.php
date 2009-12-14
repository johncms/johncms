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
    $id = intval(trim($_GET['id']));
    $typ = mysql_query("select * from `gallery` where id='" . $id . "';");
    $ms = mysql_fetch_array($typ);
    switch ($ms['type']) {
        case "al" :
            if (isset ($_POST['submit'])) {
                $text = check($_POST['text']);
                mysql_query("update `gallery` set text='" . $text . "' where id='" . $id . "';");
                header("location: index.php?id=$id");
            }
            else {
                echo "Редактирование альбома<br/><form action='index.php?act=edit&amp;id=" . $id . "' method='post'><input type='text' name='text' value='" . $ms['text'] .
                "'/><br/><input type='submit' name='submit' value='Ok!'/></form><br/><a href='index.php?id=" . $id . "'>Назад</a><br/>";
            }
            break;

        case "rz" :
            if (isset ($_POST['submit'])) {
                $text = check($_POST['text']);
                if (!empty ($_POST['user'])) {
                    $user = intval($_POST['user']);
                }
                else {
                    $user = 0;
                }
                mysql_query("update `gallery` set text='" . $text . "', user='" . $user . "' where id='" . $id . "';");
                header("location: index.php?id=$id");
            }
            else {
                echo "Редактирование раздела<br/><form action='index.php?act=edit&amp;id=" . $id . "' method='post'><input type='text' name='text' value='" . $ms['text'] . "'/><br/>";
                if ($ms['user'] == 1) {
                    echo "<input type='checkbox' name='user' value='1' checked='checked'/>Для альбомов юзеров<br/>";
                }
                else {
                    echo "<input type='checkbox' name='user' value='1'/>Для альбомов юзеров<br/>";
                }
                echo "<input type='submit' name='submit' value='Ok!'/></form><br/><a href='index.php?id=" . $id . "'>Назад</a><br/>";
            }
            break;
        default :
            echo "Ошибка<br/><a href='index.php'>В галерею</a><br/>";
            require_once ('../incfiles/end.php');
            exit;
            break;
    }
}
else {
    header("location: index.php");
}

?>