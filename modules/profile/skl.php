<?php

/**
 * This file is part of JohnCMS Content Management System.
 *
 * @copyright JohnCMS Community
 * @license   https://opensource.org/licenses/GPL-3.0 GPL-3.0
 * @link      https://johncms.com JohnCMS Project
 */

declare(strict_types=1);

use Johncms\Mail\EmailMessage;
use Johncms\System\Http\Request;
use Johncms\System\i18n\Translator;
use Johncms\System\View\Render;
use Johncms\NavChain;

$config = di('config')['johncms'];

// Register the module languages domain and folder
/** @var Translator $translator */
$translator = di(Translator::class);
$translator->addTranslationDomain('profile', __DIR__ . '/locale');

/** @var PDO $db */
$db = di(PDO::class);
/** @var Request $request */
$request = di(Request::class);

/** @var Johncms\System\Legacy\Tools $tools */
$tools = di(Johncms\System\Legacy\Tools::class);

$view = di(Render::class);

/** @var NavChain $nav_chain */
$nav_chain = di(NavChain::class);

// Регистрируем Namespace для шаблонов модуля
$view->addFolder('profile', __DIR__ . '/templates/');

$nav_chain->add(__('Restore password'));

function passgen($length)
{
    $vals = 'abcdefghijklmnopqrstuvwxyz0123456789';
    $result = '';
    for ($i = 1; $i <= $length; $i++) {
        $result .= $vals[rand(0, strlen($vals) - 1)];
    }

    return $result;
}

$id = $request->getQuery('id', 0, FILTER_VALIDATE_INT);
$act = $request->getQuery('act', '', FILTER_SANITIZE_STRING);

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
            $error = __('The required fields are not filled');
        } elseif (! isset($_SESSION['code']) || mb_strlen($code) < 3 || strtolower($code) != strtolower($_SESSION['code'])) {
            $error = __('Incorrect code');
        }

        unset($_SESSION['code']);

        if (! $error) {
            // Проверяем данные по базе
            $req = $db->prepare('SELECT `id`, `name`, `mail`, `rest_time` FROM `users` WHERE `name_lat` = ? LIMIT 1');
            $req->execute([$nick]);

            if ($req->rowCount()) {
                $res = $req->fetch();

                if (empty($res['mail']) || $res['mail'] != $email) {
                    $error = __('Invalid Email address');
                }

                if ($res['rest_time'] > time() - 86400) {
                    $error = __('Password can be recovered 1 time per day');
                }
            } else {
                $error = __('User does not exists');
            }
        }

        if (! $error) {
            // Высылаем инструкции на E-mail
            $link = $config['homeurl'] . '/profile/skl.php?act=set&id=' . $res['id'] . '&code=' . $check_code;
            $name = ! empty($res['imname']) ? htmlspecialchars($res['imname']) : $res['name'];
            (new EmailMessage())->create(
                [
                    'priority' => 1,
                    'locale'   => $translator->getLocale(),
                    'template' => 'system::mail/templates/restore_password',
                    'fields'   => [
                        'email_to'        => $res['mail'],
                        'name_to'         => $name,
                        'subject'         => __('Password recovery'),
                        'user_name'       => $name,
                        'link_to_restore' => $link,
                    ],
                ]
            );

            $req = $db->prepare('UPDATE `users` SET `rest_code` = ?, `rest_time` = ? WHERE `id` = ?');
            $req->execute([$check_code, time(), $res['id']]);
            $type = 'success';
            $message = __('Check your e-mail for further information');
        } else {
            // Выводим сообщение об ошибке
            $message = $error;
        }

        echo $view->render(
            'profile::restore_password_result',
            [
                'type'    => $type,
                'message' => $message,
            ]
        );
        break;

    case 'set':
        // Устанавливаем новый пароль
        $code = trim($request->getQuery('code', '', FILTER_SANITIZE_STRING));
        $error = false;
        $type = 'error';

        if (! $id || mb_strlen($code) !== 32) {
            $error = __('Wrong data');
        }

        if (! $error) {
            $req = $db->query('SELECT `id`, `name`, `mail`, `rest_code`, `rest_time` FROM `users` WHERE `id` = ' . $id);

            if ($req->rowCount()) {
                $res = $req->fetch();

                if (empty($res['rest_code']) || empty($res['rest_time'])) {
                    $error = __('Password recovery is impossible');
                }

                if (! $error && ($res['rest_time'] < time() - 3600 || $code != $res['rest_code'])) {
                    $error = __('Time allotted for the password recovery has been exceeded');
                    $req = $db->prepare('UPDATE `users` SET `rest_code` = "", `rest_time` = "" WHERE `id` = ?');
                    $req->execute([$res['id']]);
                }
            } else {
                $error = __('User does not exists');
            }
        }

        if (! $error) {
            // Высылаем пароль на E-mail
            $pass = passgen(4);
            $name = ! empty($res['imname']) ? htmlspecialchars($res['imname']) : $res['name'];
            (new EmailMessage())->create(
                [
                    'priority' => 1,
                    'locale'   => $translator->getLocale(),
                    'template' => 'system::mail/templates/restore_password_complete',
                    'fields'   => [
                        'email_to'      => $res['mail'],
                        'name_to'       => $name,
                        'subject'       => __('Your new password'),
                        'user_name'     => $name,
                        'user_login'    => $res['name'],
                        'user_password' => $pass,
                    ],
                ]
            );

            $req = $db->prepare('UPDATE `users` SET `rest_code` = "", `password` = ? WHERE `id` = ?');
            $req->execute([md5(md5($pass)), $res['id']]);
            $type = 'success';
            $message = __('Password successfully changed.<br>New password sent to your E-mail address.');
        } else {
            // Выводим сообщение об ошибке
            $message = $error;
        }

        echo $view->render(
            'profile::restore_password_result',
            [
                'type'    => $type,
                'message' => $message,
            ]
        );
        break;

    default:
        $code = (string) new Mobicms\Captcha\Code();
        $_SESSION['code'] = $code;
        // Показываем запрос на подтверждение выхода с сайта
        echo $view->render(
            'profile::restore_password',
            [
                'captcha' => new Mobicms\Captcha\Image($code),
            ]
        );
        break;
}
