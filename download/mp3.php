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

$r = intval($_GET['r']);
if (is_file("$filesroot/mp3temp/$r.mp3")) {
    header("location: $filesroot/mp3temp/$r.mp3");
}
else {
    require_once ("../incfiles/head.php");
    echo "Ошибка!<br/>&#187;<a href='?'>В загрузки</a><br/>";
}

?>