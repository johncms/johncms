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

define('_IN_JOHNCMS', 1);

$rootpath = '';
require_once ('incfiles/core.php');

$adres = check($_POST['adres']);
switch ($adres) {
    case "chat" :
        header("location: chat/index.php");
        break;
    case "forum" :
        header("location: forum/index.php");
        break;
    case "set" :
        header("location: str/usset.php");
        break;
    case "privat" :
        header("location: index.php?act=cab");
        break;
    case "prof" :
        header("location: str/anketa.php");
        break;
    case "lib" :
        header("location: library/index.php");
        break;
    case "down" :
        header("location: download/index.php");
        break;
    case "gallery" :
        header("location: gallery/index.php");
        break;
    case "news" :
        header("location: str/news.php");
        break;
    case "guest" :
        header("location: str/guest.php");
        break;
    case "gazen" :
        header("location: http://gazenwagen.com");
        break;

    default :
        $ar = explode(".", $adres);
        if ($ar[0] == "frm") {
            header("location: forum/index.php?id=$ar[1]");
        }
        else {
            header('location: index.php');
        }
        break;
}

?>