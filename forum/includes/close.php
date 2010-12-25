<?php

/*
////////////////////////////////////////////////////////////////////////////////
// JohnCMS                Mobile Content Management System                    //
// Project site:          http://johncms.com                                  //
// Support site:          http://gazenwagen.com                               //
////////////////////////////////////////////////////////////////////////////////
// Lead Developer:        Oleg Kasyanov   (AlkatraZ)  alkatraz@gazenwagen.com //
// Development Team:      Eugene Ryabinin (john77)    john77@gazenwagen.com   //
//                        Dmitry Liseenko (FlySelf)   flyself@johncms.com     //
////////////////////////////////////////////////////////////////////////////////
*/

defined('_IN_JOHNCMS') or die('Error: restricted access');

if (($rights != 3 && $rights < 6) || !$id) {
    header('Location: index.php');
    exit;
}
if (mysql_result(mysql_query("SELECT COUNT(*) FROM `forum` WHERE `id` = '$id' AND `type` = 't'"), 0)) {
    if (isset($_GET['closed']))
        mysql_query("UPDATE `forum` SET `edit` = '1' WHERE `id` = '$id'");
    else
        mysql_query("UPDATE `forum` SET `edit` = '0' WHERE `id` = '$id'");
}

header("Location: index.php?id=$id");

?>