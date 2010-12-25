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

define('_IN_JOHNCMS', 1);
$rootpath = '';
require('incfiles/core.php');
if ($id) {
    /*
    -----------------------------------------------------------------
    Редирект по рекламной ссылке
    -----------------------------------------------------------------
    */
    $req = mysql_query("SELECT * FROM `cms_ads` WHERE `id` = '$id'");
    if (mysql_num_rows($req)) {
        $res = mysql_fetch_assoc($req);
        $count_link = $res['count'] + 1;
        mysql_query("UPDATE `cms_ads` SET `count` = '$count_link'  WHERE `id` = '$id'");
        header('Location: ' . $res['link']);
    } else {
        header("Location: http://johncms.com/index.php?act=404");
    }
} else {
    /*
    -----------------------------------------------------------------
    Редирект по "быстрому переходу"
    -----------------------------------------------------------------
    */
    $adres = trim($_POST['adres']);
    switch ($adres) {
        case 'forum':
            header('location: ' . $set['homeurl'] . '/forum/index.php');
            break;

        case 'lib':
            header('location: ' . $set['homeurl'] . '/library/index.php');
            break;

        case 'down':
            header('location: ' . $set['homeurl'] . '/download/index.php');
            break;

        case 'gallery':
            header('location: ' . $set['homeurl'] . '/gallery/index.php');
            break;

        case 'news':
            header('location: ' . $set['homeurl'] . '/news/index.php');
            break;

        case 'guest':
            header('location: ' . $set['homeurl'] . '/guestbook/index.php');
            break;
            
        case 'gazen':
            header('location: http://gazenwagen.com');
            break;

        default :
            header('location: http://johncms.com');
            break;
    }
}
?>