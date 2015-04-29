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

if ($rights == 3 || $rights >= 6) {
    if (empty($_GET['id'])) {
        require('../incfiles/head.php');
        echo functions::display_error($lng['error_wrong_data']);
        require('../incfiles/end.php');
        exit;
    }
    $req = mysql_query("SELECT COUNT(*) FROM `forum` WHERE `id` = '" . $id . "' AND `type` = 't'");
    if (mysql_result($req, 0) > 0) {
        mysql_query("UPDATE `forum` SET  `vip` = '" . (isset($_GET['vip']) ? '1' : '0') . "' WHERE `id` = '$id'");
        header('Location: index.php?id=' . $id);
    } else {
        require('../incfiles/head.php');
        echo functions::display_error($lng['error_wrong_data']);
        require('../incfiles/end.php');
        exit;
    }
}
