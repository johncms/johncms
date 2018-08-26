<?php

/**
 * @package     JohnCMS
 * @link        http://johncms.com
 * @copyright   Copyright (C) 2008-2011 JohnCMS Community
 * @license     LICENSE.txt (see attached file)
 * @version     VERSION.txt (see attached file)
 * @author      http://johncms.com/about
 */

define('_IN_JOHNCMS', 1);

require('../incfiles/core.php');
$lng_pass = core::load_lng('pass');
$textl = $lng_pass['password_restore'];
require('../incfiles/head.php');

function passgen($length) {
    $vals = "abcdefghijklmnopqrstuvwxyz0123456789";
    $result = '';
    for ($i = 1; $i <= $length; $i++) {
        $result .= $vals{rand(0, strlen($vals))};
    }
    return $result;
}

switch ($act) {
    case 'sent':
        /*
        -----------------------------------------------------------------
        Отправляем E-mail с инструкциями по восстановлению пароля
        -----------------------------------------------------------------
        */
        $nick = isset($_POST['nick']) ? functions::rus_lat(mb_strtolower(trim($_POST['nick']))) : '';
        $email = isset($_POST['email']) ? htmlspecialchars(trim($_POST['email'])) : '';
        $code = isset($_POST['code']) ? trim($_POST['code']) : '';
        $check_code = md5(rand(1000, 9999));
        $error = false;
        if (!$nick || !$email || !$code)
            $error = $lng['error_empty_fields'];
        elseif (!isset($_SESSION['code']) || mb_strlen($code) < 4 || $code != $_SESSION['code'])
            $error = $lng_pass['error_code'];
        unset($_SESSION['code']);
        if (!$error) {
            // Проверяем данные по базе
            $stmt = $db->prepare("SELECT * FROM `users` WHERE `name_lat` = ? LIMIT 1");
            $stmt->execute([$nick]);
            if ($stmt->rowCount()) {
                $res = $stmt->fetch();
                if (empty($res['mail']) || $res['mail'] != $email)
                    $error = $lng_pass['error_email'];
                if ($res['rest_time'] > time() - 86400)
                    $error = $lng_pass['restore_timelimit'];
            } else {
                $error = $lng['error_user_not_exist'];
            }
        }
        if (!$error) {
            // Высылаем инструкции на E-mail
            $subject = $lng_pass['password_restore'];
            $mail = $lng_pass['restore_help1'] . ', ' . $res['name'] . "\r\n" . $lng_pass['restore_help2'] . ' ' . $set['homeurl'] . "\r\n";
            $mail .= $lng_pass['restore_help3'] . ": \r\n" . $set['homeurl'] . "/users/skl.php?act=set&id=" . $res['id'] . "&code=" . $check_code . "\n\n";
            $mail .= $lng_pass['restore_help4'] . "\r\n";
            $mail .= $lng_pass['restore_help5'];
            $adds = "From: <" . $set['email'] . ">\r\n";
            $adds .= "Content-Type: text/plain; charset=\"utf-8\"\r\n";
            if (mail($res['mail'], $subject, $mail, $adds)) {
                $db->exec("UPDATE `users` SET `rest_code` = '" . $check_code . "', `rest_time` = '" . time() . "' WHERE `id` = '" . $res['id'] . "'");
                echo '<div class="gmenu"><p>' . $lng_pass['restore_help6'] . '</p></div>';
            } else {
                echo '<div class="rmenu"><p>' . $lng_pass['error_email_sent'] . '</p></div>';
            }
        } else {
            // Выводим сообщение об ошибке
            echo functions::display_error($error, '<a href="skl.php">' . $lng['back'] . '</a>');
        }
        break;

    case 'set':
        /*
        -----------------------------------------------------------------
        Устанавливаем новый пароль
        -----------------------------------------------------------------
        */
        $code = isset($_GET['code']) ? trim($_GET['code']) : '';
        $error = false;
        if (!$id || !$code)
            $error = $lng['error_wrong_data'];
        $stmt = $db->query("SELECT * FROM `users` WHERE `id` = '$id' LIMIT 1");
        if ($stmt->rowCount()) {
            $res = $stmt->fetch();
            if (empty($res['rest_code']) || empty($res['rest_time'])) {
                $error = $lng_pass['error_fatal'];
            }
            if (!$error && ($res['rest_time'] < time() - 3600 || $code != $res['rest_code'])) {
                $error = $lng_pass['error_timelimit'];
                $db->exec("UPDATE `users` SET `rest_code` = '', `rest_time` = '' WHERE `id` = '$id'");
            }
        } else {
            $error = $lng['error_user_not_exist'];
        }
        if (!$error) {
            // Высылаем пароль на E-mail
            $pass = passgen(4);
            $subject = $lng_pass['your_new_password'];
            $mail = $lng_pass['restore_help1'] . ', ' . $res['name'] . "\r\n" . $lng_pass['restore_help8'] . ' ' . $set['homeurl'] . "\r\n";
            $mail .= $lng_pass['your_new_password'] . ": $pass\r\n";
            $mail .= $lng_pass['restore_help7'];
            $adds = "From: <" . $set['email'] . ">\n";
            $adds .= "Content-Type: text/plain; charset=\"utf-8\"\r\n";
            if (mail($res['mail'], $subject, $mail, $adds)) {
                $db->exec("UPDATE `users` SET `rest_code` = '', `password` = '" . md5(md5($pass)) . "' WHERE `id` = '$id'");
                echo '<div class="phdr">' . $lng_pass['change_password'] . '</div>';
                echo '<div class="gmenu"><p>' . $lng_pass['change_password_conf'] . '</p></div>';
            } else {
                echo '<div class="rmenu"><p>' . $lng_pass['error_email_sent'] . '</p></div>';
            }
        } else {
            // Выводим сообщение об ошибке
            echo functions::display_error($error);
        }
        break;

    default:
        /*
        -----------------------------------------------------------------
        Форма для восстановления пароля
        -----------------------------------------------------------------
        */
        echo '<div class="phdr"><b>' . $lng_pass['password_restore'] . '</b></div>';
        echo '<div class="menu"><form action="skl.php?act=sent" method="post">';
        echo '<p>' . $lng_pass['your_login'] . ':<br/><input type="text" name="nick" /><br/>';
        echo $lng_pass['your_email'] . ':<br/><input type="text" name="email" /></p>';
        echo '<p><img src="../captcha.php?r=' . rand(1000, 9999) . '" alt="' . $lng_pass['captcha'] . '"/><br />';
        echo '<input type="text" size="5" maxlength="5"  name="code"/>&#160;' . $lng_pass['enter_code'] . '</p>';
        echo '<p><input type="submit" value="' . $lng_pass['sent'] . '"/></p></form></div>';
        echo '<div class="phdr"><small>' . $lng_pass['restore_help'] . '</small></div>';
        break;
}

require('../incfiles/end.php');
?>