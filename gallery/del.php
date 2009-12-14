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
    if (isset ($_GET['yes'])) {
        switch ($ms['type']) {
            case "al" :
                $ft = mysql_query("select * from `gallery` where `type`='ft' and `refid`='" . $id . "';");
                while ($ft1 = mysql_fetch_array($ft)) {
                    $km = mysql_query("select * from `gallery` where type='km' and refid='" . $ft1['id'] . "';");
                    while ($km1 = mysql_fetch_array($km)) {
                        mysql_query("delete from `gallery` where `id`='" . $km1['id'] . "';");
                    }
                    unlink("foto/$ft1[name]");
                    mysql_query("delete from `gallery` where `id`='" . $ft1['id'] . "';");
                }
                mysql_query("delete from `gallery` where `id`='" . $id . "';");
                header("location: index.php?id=$ms[refid]");
                break;
            case "rz" :
                $al = mysql_query("select * from `gallery` where type='al' and refid='" . $id . "';");
                while ($al1 = mysql_fetch_array($al)) {
                    $ft = mysql_query("select * from `gallery` where type='ft' and refid='" . $al1['id'] . "';");
                    while ($ft1 = mysql_fetch_array($ft)) {
                        $km = mysql_query("select * from `gallery` where type='km' and refid='" . $ft1['id'] . "';");
                        while ($km1 = mysql_fetch_array($km)) {
                            mysql_query("delete from `gallery` where `id`='" . $km1['id'] . "';");
                        }
                        unlink("foto/$ft1[name]");
                        mysql_query("delete from `gallery` where `id`='" . $ft1['id'] . "';");
                    }
                    mysql_query("delete from `gallery` where `id`='" . $al1['id'] . "';");
                }
                mysql_query("delete from `gallery` where `id`='" . $id . "';");
                header("location: index.php");
                break;
            default :
                echo "Ошибка<br/><a href='index.php'>В галерею</a><br/>";
                require_once ('../incfiles/end.php');
                exit;
                break;
        }
    }
    else {
        switch ($ms['type']) {
            case "al" :
                echo "Вы уверены в удалении альбома $ms[text]?<br/>";
                break;
            case "rz" :
                echo "Вы уверены в удалении раздела $ms[text]?<br/>";
                break;
            default :
                echo "Ошибка<br/><a href='index.php'>В галерею</a><br/>";
                require_once ('../incfiles/end.php');
                exit;
                break;
        }
        echo "<a href='index.php?act=del&amp;id=" . $id . "&amp;yes'>Да</a> | <a href='index.php?id=" . $id . "'>Нет</a><br/>";
    }
}
else {
    header("location: index.php");
}

?>