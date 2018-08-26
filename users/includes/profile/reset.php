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

if ($rights >= 7 && $rights > $user['rights']) {
    /*
    -----------------------------------------------------------------
    Сброс настроек пользователя
    -----------------------------------------------------------------
    */
    $textl = htmlspecialchars($user['name']) . ': ' . $lng_profile['profile_edit'];
    require('../incfiles/head.php');
    $db->exec("UPDATE `users` SET `set_user` = '', `set_forum` = '' WHERE `id` = '" . $user['id'] . "' LIMIT 1");
    echo '<div class="gmenu"><p>' . $lng_profile['reset1'] . ' <b>' . $user['name'] . '</b> ' . $lng_profile['reset2'] . '<br />' .
    '<a href="profile.php?user=' . $user['id'] . '">' . $lng['profile'] . '</a></p></div>';
    require_once ('../incfiles/end.php');
    exit;
}
