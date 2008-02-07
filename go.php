<?php
/*
////////////////////////////////////////////////////////////////////////////////
// JohnCMS v.1.0.0 RC1                                                        //
// Дата релиза: 08.02.2008                                                    //
// Авторский сайт: http://gazenwagen.com                                      //
////////////////////////////////////////////////////////////////////////////////
// Оригинальная идея и код: Евгений Рябинин aka JOHN77                        //
// E-mail: 
// Модификация, оптимизация и дизайн: Олег Касьянов aka AlkatraZ              //
// E-mail: alkatraz@batumi.biz                                                //
// Плагиат и удаление копирайтов заруганы на ближайших родственников!!!       //
////////////////////////////////////////////////////////////////////////////////
// Внимание!                                                                  //
// Авторские версии данных скриптов публикуются ИСКЛЮЧИТЕЛЬНО на сайте        //
// http://gazenwagen.com                                                      //
// Если Вы скачали данный скрипт с другого сайта, то его работа не            //
// гарантируется и поддержка не оказывается.                                  //
////////////////////////////////////////////////////////////////////////////////
*/

define('_IN_PUSTO', 1);
require ("incfiles/db.php");
require ("incfiles/func.php");
require ("incfiles/data.php");
$adres = check($_POST['adres']);
switch ($adres)
{
    case "chat":
        header("location: chat/index.php");
        break;
    case "forum":
        header("location: forum/index.php");
        break;
    case "set":
        header("location: str/usset.php");
        break;
    case "privat":
        header("location: str/privat.php");
        break;
    case "prof":
        header("location: str/anketa.php");
        break;
    case "lib":
        header("location: str/lib.php");
        break;
    case "down":
        header("location: download/download.php");
        break;
    case "upl":
        header("location: download/upload.php");
        break;
    case "gallery":
        header("location: gallery/index.php");
        break;
    case "news":
        header("location: str/news.php");
        break;
    case "znak":
        header("location: str/znak.php");
        break;
    case "guest":
        header("location: str/guest.php");
        break;
    case "gazen":
        header("location: http://gazenwagen.com");
        break;

    default:
        $ar = explode(".", $adres);
        if ($ar[0] == "frm")
        {
            header("location: forum/index.php?id=$ar[1]");
        } else
        {
            header("location: index.php");
        }
        break;
}

?>