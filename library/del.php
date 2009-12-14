<?php

/*
////////////////////////////////////////////////////////////////////////////////
// JohnCMS                                                                    //
// Официальный сайт сайт проекта:      http://johncms.com                     //
// Дополнительный сайт поддержки:      http://gazenwagen.com                  //
////////////////////////////////////////////////////////////////////////////////
// JohnCMS core team:                                                         //
// Евгений Рябинин aka john77          john77@johncms.com                     //
// Олег Касьянов aka AlkatraZ          alkatraz@johncms.com                   //
//                                                                            //
// Информацию о версиях смотрите в прилагаемом файле version.txt              //
////////////////////////////////////////////////////////////////////////////////
*/

defined('_IN_JOHNCMS') or die('Error: restricted access');

if ($rights == 5 || $rights >= 6) {
    if ($_GET['id'] == "" || $_GET['id'] == "0") {
        echo "Ошибка<br/><a href='index.php?'>В библиотеку</a><br/>";
        require_once ('../incfiles/end.php');
        exit;
    }
    $id = intval(trim($_GET['id']));
    $typ = mysql_query("select * from `lib` where id='" . $id . "';");
    $ms = mysql_fetch_array($typ);
    $rid = $ms['refid'];
    if (isset ($_GET['yes'])) {
        switch ($ms['type']) {
            case "komm" :
                mysql_query("delete from `lib` where `id`='" . $id . "';");
                header("location: index.php?act=komm&id=$rid");
                break;
            case "bk" :
                $km = mysql_query("select `id` from `lib` where type='komm' and refid='" . $id . "';");
                while ($km1 = mysql_fetch_array($km)) {
                    mysql_query("delete from `lib` where `id`='" . $km1['id'] . "';");
                }
                mysql_query("delete from `lib` where `id`='" . $id . "';");
                header("location: index.php?id=$rid");
                break;
            case "cat" :
                $ct = mysql_query("select `id` from `lib` where type='cat' and refid='" . $id . "';");
                $ct1 = mysql_num_rows($ct);
                if ($ct1 != 0) {
                    echo "Сначала удалите вложенные категории<br/><a href='index.php?id=" . $id . "'>Назад</a><br/>";
                    require_once ('../incfiles/end.php');
                    exit;
                }
                $st = mysql_query("select `id` from `lib` where type='bk' and refid='" . $id . "';");
                while ($st1 = mysql_fetch_array($st)) {
                    $km = mysql_query("select `id` from `lib` where type='komm' and refid='" . $st1['id'] . "';");
                    while ($km1 = mysql_fetch_array($km)) {
                        mysql_query("delete from `lib` where `id`='" . $km1['id'] . "';");
                    }

                    mysql_query("delete from `lib` where `id`='" . $st1['id'] . "';");
                }
                mysql_query("delete from `lib` where `id`='" . $id . "';");
                header("location: index.php?id=$rid");
                break;
        }
    }
    else {
        switch ($ms['type']) {
            case "komm" :
                header("location: index.php?act=del&id=$id&yes");
                break;
            case "bk" :
                echo "Вы уверены в удалении статьи?<br/><a href='index.php?act=del&amp;id=" . $id . "&amp;yes'>Да</a> | <a href='index.php?id=" . $id .
                "'>Нет</a><br/><a href='index.php'>В галерею</a><br/>";
                break;
            case "cat" :
                $ct = mysql_query("select `id` from `lib` where type='cat' and refid='" . $id . "';");
                $ct1 = mysql_num_rows($ct);
                if ($ct1 != 0) {
                    echo "Сначала удалите вложенные категории<br/><a href='index.php?id=" . $id . "'>Назад</a><br/>";
                    require_once ('../incfiles/end.php');
                    exit;
                }
                echo "Вы уверены в удалении категории?<br/><a href='index.php?act=del&amp;id=" . $id . "&amp;yes'>Да</a> | <a href='index.php?id=" . $id .
                "'>Нет</a><br/><a href='index.php'>В галерею</a><br/>";
                break;
        }
    }
}
else {
    header("location: index.php");
}

?>