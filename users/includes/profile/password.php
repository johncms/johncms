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

/*
-----------------------------------------------------------------
Проверяем права доступа
-----------------------------------------------------------------
*/
if ($user['id'] != $user_id && ($rights < 7 || $user['rights'] > $rights)) {
    echo functions::display_error($lng['access_forbidden']);
    require('../incfiles/end.php');
    exit;
}
$lng_pass = core::load_lng('pass');
$textl = htmlspecialchars($user['name']) . ': ' . $lng_pass['change_password'];
require('../incfiles/head.php');

switch ($mod) {
    case 'change':
        /*
        -----------------------------------------------------------------
        Меняем пароль
        -----------------------------------------------------------------
        */
        $error = array();
        $oldpass = isset($_POST['oldpass']) ? trim($_POST['oldpass']) : '';
        $newpass = isset($_POST['newpass']) ? trim($_POST['newpass']) : '';
        $newconf = isset($_POST['newconf']) ? trim($_POST['newconf']) : '';
        $autologin = isset($_POST['autologin']) ? 1 : 0;
        if ($user['id'] != $user_id) {
            if (!$newpass || !$newconf) {
                $error[] = $lng_pass['error_fields'];
            }
        } else {
            if (!$oldpass || !$newpass || !$newconf) {
                $error[] = $lng_pass['error_fields'];
            }
        }
        if (!$error && $user['id'] == $user_id && md5(md5($oldpass)) !== $user['password']) {
            $error[] = $lng_pass['error_old_password'];
        }
        if ($newpass != $newconf) {
            $error[] = $lng_pass['error_new_password'];
        }
        if (!$error && strlen($newpass) < 6) {
            $error[] = $lng_pass['error_lenght'];
        }
        if (!$error) {
            // Записываем в базу
            $db->exec("UPDATE `users` SET `password` = '" . md5(md5($newpass)) . "' WHERE `id` = '" . $user['id'] . "' LIMIT 1");
            // Проверяем и записываем COOKIES
            if ($user_id == $user['id'] && isset($_COOKIE['cuid']) && isset($_COOKIE['cups'])) {
                setcookie('cups', md5($newpass), time() + 3600 * 24 * 365);
            }
            echo '<div class="gmenu"><p><b>' . $lng_pass['password_changed'] . '</b><br />' .
                '<a href="profile.php?user=' . $user['id'] . '">' . $lng['continue'] . '</a></p>';
            echo '</div>';
        } else {
            echo functions::display_error($error, '<a href="profile.php?act=password&amp;user=' . $user['id'] . '">' . $lng['repeat'] . '</a>');
        }
        break;

    default:
        /*
        -----------------------------------------------------------------
        Форма смены пароля
        -----------------------------------------------------------------
        */
        echo '<div class="phdr"><b>' . $lng_pass['change_password'] . ':</b> ' . $user['name'] . '</div>';
        echo '<form action="profile.php?act=password&amp;mod=change&amp;user=' . $user['id'] . '" method="post">';
        if ($user['id'] == $user_id) {
            echo '<div class="menu"><p>' . $lng_pass['input_old_password'] . ':<br /><input type="password" name="oldpass" /></p></div>';
        }
        echo '<div class="gmenu"><p>' . $lng_pass['input_new_password'] . ':<br />' .
            '<input type="password" name="newpass" /><br />' . $lng_pass['repeat_password'] . ':<br />' .
            '<input type="password" name="newconf" /></p>' .
            '<p><input type="submit" value="' . $lng['save'] . '" name="submit" />' .
            '</p></div></form>' .
            '<div class="phdr"><small>' . $lng_pass['password_change_help'] . '</small></div>' .
            '<p><a href="profile.php?user=' . $user['id'] . '">' . $lng['profile'] . '</a></p>';
}
