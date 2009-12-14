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

if (($rights != 3 && $rights < 6) || !$id) {
    header('Location: index.php');
    exit;
}
if (mysql_result(mysql_query("SELECT COUNT(*) FROM `forum` WHERE `id` = '$id' AND `type` = 't'"), 0)) {
    if (isset ($_GET['closed']))
        mysql_query("UPDATE `forum` SET `edit` = '1' WHERE `id` = '$id'");
    else
        mysql_query("UPDATE `forum` SET `edit` = '0' WHERE `id` = '$id'");
}

header("Location: index.php?id=$id");

?>