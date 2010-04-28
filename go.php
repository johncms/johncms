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
require_once('incfiles/core.php');

$adres = trim($_POST['adres']);
switch ($adres) {
    case 'chat':
        header("location: $home/chat/index.php");
        break;

    case 'forum':
        header("location: $home/forum/index.php");
        break;

    case 'set':
        header("location: $home/str/usset.php");
        break;

    case 'privat':
        header("location: $home/index.php?act=cab");
        break;

    case 'prof':
        header("location: $home/str/anketa.php");
        break;

    case 'lib':
        header("location: $home/library/index.php");
        break;

    case 'down':
        header("location: $home/download/index.php");
        break;

    case 'gallery':
        header("location: $home/gallery/index.php");
        break;

    case 'news':
        header("location: $home/str/news.php");
        break;

    case 'guest':
        header("location: $home/str/guest.php");
        break;

    default :
        header("location: http://gazenwagen.com");
        break;
}

?>