<?php

/**
 * This file is part of JohnCMS Content Management System.
 *
 * @copyright JohnCMS Community
 * @license   https://opensource.org/licenses/GPL-3.0 GPL-3.0
 * @link      https://johncms.com JohnCMS Project
 */

declare(strict_types=1);

use Johncms\Api\NavChainInterface;
use Johncms\System\Config\Config;
use Johncms\View\Render;

$id = isset($_GET['id']) ? abs((int) ($_GET['id'])) : 0;
$act = isset($_GET['act']) ? trim($_GET['act']) : '';

/** @var Config $config */
$config = di(Config::class);

/** @var Zend\I18n\Translator\Translator $translator */
$translator = di(Zend\I18n\Translator\Translator::class);
$translator->addTranslationFilePattern('gettext', __DIR__ . '/locale', '/%s/default.mo');

/** @var PDO $db */
$db = di(PDO::class);

/** @var Johncms\Api\ToolsInterface $tools */
$tools = di(Johncms\Api\ToolsInterface::class);

$view = di(Render::class);

/** @var NavChainInterface $nav_chain */
$nav_chain = di(NavChainInterface::class);

// Регистрируем Namespace для шаблонов модуля
$view->addFolder('profile', __DIR__ . '/templates/');

$nav_chain->add(_t('Restore password', 'system'));

function passgen($length)
{
    $vals = 'abcdefghijklmnopqrstuvwxyz0123456789';
    $result = '';
    for ($i = 1; $i <= $length; $i++) {
        $result .= $vals[rand(0, strlen($vals) - 1)];
    }

    return $result;
}

switch ($act) {
    case 'sent':
        // Отправляем E-mail с инструкциями по восстановлению пароля
        $nick = isset($_POST['nick']) ? $tools->rusLat($_POST['nick']) : '';
        $email = isset($_POST['email']) ? htmlspecialchars(trim($_POST['email'])) : '';
        $code = isset($_POST['code']) ? trim($_POST['code']) : '';
        $rand = (string) rand(1000, 9999);
        $check_code = md5($rand);
        $error = false;
        $type = 'error';

        if (! $nick || ! $email || ! $code) {
            $error = _t('The required fields are not filled');
        } elseif (! isset($_SESSION['code']) || mb_strlen($code) < 4 || $code != $_SESSION['code']) {
            $error = _t('Incorrect code');
        }

        unset($_SESSION['code']);

        if (! $error) {
            // Проверяем данные по базе
            $req = $db->query('SELECT * FROM `users` WHERE `name_lat` = ' . $db->quote($nick) . ' LIMIT 1');

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

        if (! $error) {
            // Высылаем инструкции на E-mail
            $link = $config['homeurl'] . '/profile/skl.php?act=set&id=' . $res['id'] . '&code=' . $check_code;
            $subject = _t('Password recovery');
            $mail = sprintf(
                _t("Hello %s!\nYou start process of password recovery on the site %s\nIn order to recover your password, you must click on the link: %s\nLink valid for 1 hour\n\nIf you receive this mail by mistake, just ignore this letter"),
                $res['name'],
                $config['homeurl'],
                $link
            );
            $adds = 'From: <' . $config['email'] . ">\r\nContent-Type: text/plain; charset=\"utf-8\"\r\n";

            if (mail($res['mail'], $subject, $mail, $adds)) {
                $db->exec('UPDATE `users` SET `rest_code` = ' . $db->quote($check_code) . ", `rest_time` = '" . time() . "' WHERE `id` = " . $res['id']);
                $type = 'success';
                $message = _t('Check your e-mail for further information');
            } else {
                $message = _t('Error sending E-mail');
            }
        } else {
            // Выводим сообщение об ошибке
            $message = $error;
        }

        echo $view->render('profile::restore_password_result', [
            'type'    => $type,
            'message' => $message,
        ]);
        break;

    case 'set':
        // Устанавливаем новый пароль
        $code = isset($_GET['code']) ? trim($_GET['code']) : '';
        $error = false;
        $type = 'error';

        if (! $id || ! $code) {
            $error = _t('Wrong data');
        }

        if (! empty($id)) {
            $req = $db->query('SELECT * FROM `users` WHERE `id` = ' . $id);

            if ($req->rowCount()) {
                $res = $req->fetch();

                if (empty($res['rest_code']) || empty($res['rest_time'])) {
                    $error = _t('Password recovery is impossible');
                }

                if (! $error && ($res['rest_time'] < time() - 3600 || $code != $res['rest_code'])) {
                    $error = _t('Time allotted for the password recovery has been exceeded');
                    $db->exec("UPDATE `users` SET `rest_code` = '', `rest_time` = '' WHERE `id` = " . $id);
                }
            } else {
                $error = _t('User does not exists');
            }
        }

        if (! $error) {
            // Высылаем пароль на E-mail
            $pass = passgen(4);
            $subject = _t('Your new password');
            $mail = sprintf(
                _t("Hello %s\nYou have changed your password on the site %s\n\nYour new password: %s\n\nAfter logging in, you can change your password to new one."),
                $res['name'],
                $config['homeurl'],
                $pass
            );
            $adds = 'From: <' . $config['email'] . ">\nContent-Type: text/plain; charset=\"utf-8\"\n";

            if (mail($res['mail'], $subject, $mail, $adds)) {
                $db->exec("UPDATE `users` SET `rest_code` = '', `password` = " . $db->quote(md5(md5($pass))) . ' WHERE `id` = ' . $id);
                $type = 'success';
                $message = _t('Password successfully changed.<br>New password sent to your E-mail address.');
            } else {
                $message = _t('Error sending E-mail');
            }
        } else {
            // Выводим сообщение об ошибке
            $message = $error;
        }

        echo $view->render('profile::restore_password_result', [
            'type'    => $type,
            'message' => $message,
        ]);
        break;

    default:
        $code = (string) new Mobicms\Captcha\Code;
        $_SESSION['code'] = $code;
        // Показываем запрос на подтверждение выхода с сайта
        echo $view->render('profile::restore_password', [
            'captcha' => new Mobicms\Captcha\Image($code),
        ]);
        break;
}
