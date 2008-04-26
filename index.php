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

$headmod = "mainpage";

// Внимание! Если файл находится в корневой папке, нужно указать $rootpath = '';
$rootpath = '';

require_once ("incfiles/core.php");
require_once ("incfiles/head.php");

if (isset($_GET['err']))
{
    echo '<div style="text-align: center">Ошибка 404: файл не найден!!!</div>';
}

include "pages/mainmenu.php";
require_once ("incfiles/end.php");

?>