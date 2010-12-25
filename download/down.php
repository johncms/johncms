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

$fil = mysql_query("select * from `download` where id='$id';");
$mas = mysql_fetch_array($fil);
if (!empty ($mas[name])) {
    if (file_exists("$mas[adres]/$mas[name]")) {
        $sc = $mas[ip] + 1;
        mysql_query("update `download` set ip = '" . $sc . "' where id = '" . $id . "';");
        $_SESSION['upl'] = "";
        header("location: $mas[adres]/$mas[name]");
    }
}
else {
    require_once ("../incfiles/head.php");
    echo "ERROR<br/>&#187;<a href='?'>Back</a><br/>";
}

?>