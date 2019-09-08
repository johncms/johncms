<?php
/*
 * JohnCMS NEXT Mobile Content Management System (http://johncms.com)
 *
 * For copyright and license information, please see the LICENSE.md
 * Installing the system or redistributions of files must retain the above copyright notice.
 *
 * @link        http://johncms.com JohnCMS Project
 * @copyright   Copyright (C) JohnCMS Community
 * @license     GPL-3
 */

define('_IN_JOHNCMS', 1);

require('../system/bootstrap.php');

$id = isset($_GET['id']) ? abs(intval($_GET['id'])) : 0;
$act = isset($_GET['act']) ? trim($_GET['act']) : '';

/** @var Psr\Container\ContainerInterface $container */
$container = App::getContainer();

/** @var Johncms\Api\ConfigInterface $config */
$config = $container->get(Johncms\Api\ConfigInterface::class);

/** @var Zend\I18n\Translator\Translator $translator */
$translator = $container->get(Zend\I18n\Translator\Translator::class);
$translator->addTranslationFilePattern('gettext', __DIR__ . '/locale', '/%s/default.mo');

/** @var PDO $db */
$db = $container->get(PDO::class);

/** @var Johncms\Api\ToolsInterface $tools */
$tools = $container->get(Johncms\Api\ToolsInterface::class);

$textl = _t('Password recovery');
require('../system/head.php');

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
        $nick = isset($_POST['nick']) ? $tools->rusLat($_POST['nick']) : '';
        $email = isset($_POST['email']) ? htmlspecialchars(trim($_POST['email'])) : '';
        $code = isset($_POST['code']) ? trim($_POST['code']) : '';
        $check_code = md5(rand(1000, 9999));
        $error = false;

        if (!$nick || !$email || !$code) {
            $error = _t('The required fields are not filled');
        } elseif (!isset($_SESSION['code']) || mb_strlen($code) < 4 || $code != $_SESSION['code']) {
            $error = _t('Incorrect code');
        }

        unset($_SESSION['code']);

        if (!$error) {
            // Проверяем данные по базе
            $req = $db->query("SELECT * FROM `users` WHERE `name_lat` = " . $db->quote($nick) . " LIMIT 1");

            if ($req->rowCount()) {
                $res = $req->fetch();

                if (empty($res['mail']) || $res['mail'] != $email) {
                    $error = _t('Invalid Email address');
                }

                if ($res['rest_time'] > time() - 86400) {
                    $error = _t('Password can be recovered 1 time per day');
                }
            } else {
                $error = _t('User does not exists');
            }
        }

        if (!$error) {
            // Высылаем инструкции на E-mail
            $link = $config['homeurl'] . '/profile/skl.php?act=set&id=' . $res['id'] . '&code=' . $check_code;
            $subject = _t('Password recovery');
            $mail = sprintf(
                _t("Hello %s!\nYou start process of password recovery on the site %s\nIn order to recover your password, you must click on the link: %s\nLink valid for 1 hour\n\nIf you receive this mail by mistake, just ignore this letter"),
                $res['name'],
                $config['homeurl'],
                $link
            );
            $adds = "From: <" . $config['email'] . ">\r\nContent-Type: text/plain; charset=\"utf-8\"\r\n";

            if (mail($res['mail'], $subject, $mail, $adds)) {
                $db->exec("UPDATE `users` SET `rest_code` = " . $db->quote($check_code) . ", `rest_time` = '" . time() . "' WHERE `id` = " . $res['id']);
                echo '<div class="gmenu"><p>' . _t('Check your e-mail for further information') . '</p></div>';
            } else {
                echo '<div class="rmenu"><p>' . _t('Error sending E-mail') . '</p></div>';
            }
        } else {
            // Выводим сообщение об ошибке
            echo $tools->displayError($error, '<a href="skl.php">' . _t('Back') . '</a>');
        }
        break;

    case 'set':
        // Устанавливаем новый пароль
        $code = isset($_GET['code']) ? trim($_GET['code']) : '';
        $error = false;

        if (!$id || !$code) {
            $error = _t('Wrong data');
        }

        $req = $db->query("SELECT * FROM `users` WHERE `id` = " . $id);

        if ($req->rowCount()) {
            $res = $req->fetch();

            if (empty($res['rest_code']) || empty($res['rest_time'])) {
                $error = _t('Password recovery is impossible');
            }

            if (!$error && ($res['rest_time'] < time() - 3600 || $code != $res['rest_code'])) {
                $error = _t('Time allotted for the password recovery has been exceeded');
                $db->exec("UPDATE `users` SET `rest_code` = '', `rest_time` = '' WHERE `id` = " . $id);
            }
        } else {
            _t('User does not exists');
        }

        if (!$error) {
            // Высылаем пароль на E-mail
            $pass = passgen(4);
            $subject = _t('Your new password');
            $mail = sprintf(
                _t("Hello %s\nYou have changed your password on the site %s\n\nYour new password: %s\n\nAfter logging in, you can change your password to new one."),
                $res['name'],
                $config['homeurl'],
                $pass
            );
            $adds = "From: <" . $config['email'] . ">\nContent-Type: text/plain; charset=\"utf-8\"\n";

            if (mail($res['mail'], $subject, $mail, $adds)) {
                $db->exec("UPDATE `users` SET `rest_code` = '', `password` = " . $db->quote(md5(md5($pass))) . " WHERE `id` = " . $id);
                echo '<div class="phdr">' . _t('Change password') . '</div>';
                echo '<div class="gmenu"><p>' . _t('Password successfully changed.<br>New password sent to your E-mail address.') . '</p></div>';
            } else {
                echo '<div class="rmenu"><p>' . _t('Error sending Email') . '</p></div>';
            }
        } else {
            // Выводим сообщение об ошибке
            echo $tools->displayError($error);
        }
        break;

    default:
        // Форма для восстановления пароля
        echo '<div class="phdr"><b>' . _t('Password recovery') . '</b></div>';
        echo '<div class="menu"><form action="skl.php?act=sent" method="post">';
        echo '<p>' . _t('Username') . ':<br><input type="text" name="nick" /><br>';
        echo _t('Your E-mail') . ':<br><input type="text" name="email" /></p>';
        echo '<p><img src="../captcha.php?r=' . rand(1000, 9999) . '" alt="' . _t('Verification code') . '"/><br />';
        echo '<input type="text" size="5" maxlength="5"  name="code"/>&#160;' . _t('Enter code') . '</p>';
        echo '<p><input type="submit" value="' . _t('Send') . '"/></p></form></div>';
        echo '<div class="phdr"><small>' . _t('Password will be send to E-mail address specified in your profile.<br />WARNING !! If E-mail address has not been specified in your profile, you will not be able to recover your password.') . '</small></div>';
        break;
}

require('../system/end.php');
