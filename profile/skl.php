<?php

define('_IN_JOHNCMS', 1);

require('../incfiles/core.php');

// Задаем домен для перевода
_setDomain('profile');

$textl = _td('Password recovery');
require('../incfiles/head.php');

/** @var PDO $db */
$db = App::getContainer()->get(PDO::class);

function passgen($length)
{
    $vals = "abcdefghijklmnopqrstuvwxyz0123456789";
    $result = '';
    for ($i = 1; $i <= $length; $i++) {
        $result .= $vals{rand(0, strlen($vals))};
    }

    return $result;
}

switch ($act) {
    case 'sent':
        // Отправляем E-mail с инструкциями по восстановлению пароля
        $nick = isset($_POST['nick']) ? functions::rus_lat(mb_strtolower($_POST['nick'])) : '';
        $email = isset($_POST['email']) ? htmlspecialchars(trim($_POST['email'])) : '';
        $code = isset($_POST['code']) ? trim($_POST['code']) : '';
        $check_code = md5(rand(1000, 9999));
        $error = false;

        if (!$nick || !$email || !$code) {
            $error = _td('The required fields are not filled');
        } elseif (!isset($_SESSION['code']) || mb_strlen($code) < 4 || $code != $_SESSION['code']) {
            $error = _td('Incorrect code');
        }

        unset($_SESSION['code']);

        if (!$error) {
            // Проверяем данные по базе
            $req = $db->query("SELECT * FROM `users` WHERE `name_lat` = " . $db->quote($nick) . " LIMIT 1");

            if ($req->rowCount()) {
                $res = $req->fetch();

                if (empty($res['mail']) || $res['mail'] != $email) {
                    $error = _td('Invalid Email address');
                }

                if ($res['rest_time'] > time() - 86400) {
                    $error = _td('Password can be recovered 1 time per day');
                }
            } else {
                $error = _td('User does not exists');
            }
        }

        if (!$error) {
            // Высылаем инструкции на E-mail
            $link = $set['homeurl'] . '/profile/skl.php?act=set&id=' . $res['id'] . '&code=' . $check_code;
            $subject = _td('Password recovery');
            $mail = sprintf(
                _td("Hello %s!\nYou start process of password recovery on the site %s\nIn order to recover your password, you must click on the link: %s\nLink valid for 1 hour\n\nIf you receive this mail by mistake, just ignore this letter"),
                $res['name'],
                $set['homeurl'],
                $link
            );
            $adds = "From: <" . $set['email'] . ">\r\nContent-Type: text/plain; charset=\"utf-8\"\r\n";

            if (mail($res['mail'], $subject, $mail, $adds)) {
                $db->exec("UPDATE `users` SET `rest_code` = " . $db->quote($check_code) . ", `rest_time` = '" . time() . "' WHERE `id` = " . $res['id']);
                echo '<div class="gmenu"><p>' . _td('Check your e-mail for further information') . '</p></div>';
            } else {
                echo '<div class="rmenu"><p>' . _td('Error sending E-mail') . '</p></div>';
            }
        } else {
            // Выводим сообщение об ошибке
            echo functions::display_error($error, '<a href="skl.php">' . _td('Back') . '</a>');
        }
        break;

    case 'set':
        // Устанавливаем новый пароль
        $code = isset($_GET['code']) ? trim($_GET['code']) : '';
        $error = false;

        if (!$id || !$code) {
            $error = _td('Wrong data');
        }

        $req = $db->query("SELECT * FROM `users` WHERE `id` = " . $id);

        if ($req->rowCount()) {
            $res = $req->fetch();

            if (empty($res['rest_code']) || empty($res['rest_time'])) {
                $error = _td('Password recovery is impossible');
            }

            if (!$error && ($res['rest_time'] < time() - 3600 || $code != $res['rest_code'])) {
                $error = _td('Time allotted for the password recovery has been exceeded');
                $db->exec("UPDATE `users` SET `rest_code` = '', `rest_time` = '' WHERE `id` = " . $id);
            }
        } else {
            _td('User does not exists');
        }

        if (!$error) {
            // Высылаем пароль на E-mail
            $pass = passgen(4);
            $subject = _td('Your new password');
            $mail = sprintf(
                _td("Hello %s\nYou have changed your password on the site %s\n\nYour new password: %s\n\nAfter logging in, you can change your password to new one."),
                $res['name'],
                $set['homeurl'],
                $pass
            );
            $adds = "From: <" . $set['email'] . ">\nContent-Type: text/plain; charset=\"utf-8\"\n";

            if (mail($res['mail'], $subject, $mail, $adds)) {
                $db->exec("UPDATE `users` SET `rest_code` = '', `password` = " . $db->quote(md5(md5($pass))) . " WHERE `id` = " . $id);
                echo '<div class="phdr">' . _td('Change password') . '</div>';
                echo '<div class="gmenu"><p>' . _td('Password successfully changed.<br>New password sent to your E-mail address.') . '</p></div>';
            } else {
                echo '<div class="rmenu"><p>' . _td('Error sending Email') . '</p></div>';
            }
        } else {
            // Выводим сообщение об ошибке
            echo functions::display_error($error);
        }
        break;

    default:
        // Форма для восстановления пароля
        echo '<div class="phdr"><b>' . _td('Password recovery') . '</b></div>';
        echo '<div class="menu"><form action="skl.php?act=sent" method="post">';
        echo '<p>' . _td('Username') . ':<br/><input type="text" name="nick" /><br/>';
        echo _td('Your E-mail') . ':<br/><input type="text" name="email" /></p>';
        echo '<p><img src="../captcha.php?r=' . rand(1000, 9999) . '" alt="' . _td('Verification code') . '"/><br />';
        echo '<input type="text" size="5" maxlength="5"  name="code"/>&#160;' . _td('Enter code') . '</p>';
        echo '<p><input type="submit" value="' . _td('Send') . '"/></p></form></div>';
        echo '<div class="phdr"><small>' . _td('Password will be send to E-mail address specified in your profile.<br />WARNING !! If E-mail address has not been specified in your profile, you will not be able to recover your password.') . '</small></div>';
        break;
}

require('../incfiles/end.php');
