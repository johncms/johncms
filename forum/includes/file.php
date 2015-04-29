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
$error = false;
if ($id) {
    /*
    -----------------------------------------------------------------
    Скачивание прикрепленного файла Форума
    -----------------------------------------------------------------
    */
    $req = mysql_query("SELECT * FROM `cms_forum_files` WHERE `id` = '$id'");
    if (mysql_num_rows($req)) {
        $res = mysql_fetch_array($req);
        if (file_exists('../files/forum/attach/' . $res['filename'])) {
            $dlcount = $res['dlcount'] + 1;
            mysql_query("UPDATE `cms_forum_files` SET  `dlcount` = '$dlcount' WHERE `id` = '$id'");
            header('location: ../files/forum/attach/' . $res['filename']);
        } else {
            $error = true;
        }
    } else {
        $error = true;
    }
    if ($error) {
        require('../incfiles/head.php');
        echo functions::display_error($lng['error_file_not_exist'], '<a href="index.php">' . $lng['to_forum'] . '</a>');
        require('../incfiles/end.php');
        exit;
    }
} else {
    header('location: index.php');
}
