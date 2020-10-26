<?php

/**
 * This file is part of JohnCMS Content Management System.
 *
 * @copyright JohnCMS Community
 * @license   https://opensource.org/licenses/GPL-3.0 GPL-3.0
 * @link      https://johncms.com JohnCMS Project
 */

declare(strict_types=1);

use Illuminate\Support\Str;
use Johncms\System\Http\Request;
use Johncms\System\Legacy\Tools;
use Johncms\System\Users\User;
use Johncms\System\View\Render;
use Johncms\NavChain;

defined('_IN_JOHNCMS') || die('Error: restricted access');

/**
 * @var Request $request
 * @var User $user
 * @var Render $view
 * @var NavChain $nav_chain
 */

$config = di('config')['johncms'];
$request = di(Request::class);
$user = di(User::class);
$view = di(Render::class);
$nav_chain = di(NavChain::class);

// Регистрируем Namespace для шаблонов модуля
$view->addFolder('login', __DIR__ . '/templates/');

$id = $request->getPost('id', 0, FILTER_SANITIZE_NUMBER_INT);
$referer = $request->getServer('HTTP_REFERER', $config['homeurl'], FILTER_SANITIZE_SPECIAL_CHARS);

if ($user->isValid()) {
    ////////////////////////////////////////////////////////////
    // Выход с сайта                                          //
    ////////////////////////////////////////////////////////////
    if (isset($_POST['logout'])) {
        // Выход с сайта: удаляем COOKIE и очищаем сессию
        setcookie('cuid', '', time() - 3600, '/');
        setcookie('cups', '', time() - 3600, '/');
        $_SESSION = [];
        header('Location: /');
        exit;
    }
    $nav_chain->add(__('Personal'), '/profile/?act=office');
    $nav_chain->add(__('Logout'));
    // Показываем запрос на подтверждение выхода с сайта
    echo $view->render('login::logout', ['referer' => $referer]);
} else {
    ////////////////////////////////////////////////////////////
    // Вход на сайт                                           //
    ////////////////////////////////////////////////////////////

    /** @var PDO $db */
    $db = di(PDO::class);

    /** @var Tools $tools */
    $tools = di(Tools::class);

    $nav_chain->add(__('Login'));

    $error = [];
    $captcha = false;
    $display_form = 1;
    $user_login = $request->getPost('n', null, FILTER_SANITIZE_STRING);
    $user_pass = $request->getPost('p', null, FILTER_SANITIZE_STRING);
    $captchaCode = $request->getPost('code', null, FILTER_SANITIZE_STRING);

    if (empty($user_login)) {
        $error[] = __('You have not entered login');
    }

    if (empty($user_pass)) {
        $error[] = __('You have not entered password');
    }

    if (! $error) {
        // Запрос в базу на юзера
        $stmt = $db->prepare('SELECT * FROM `users` WHERE `name_lat` = ? LIMIT 1');
        $stmt->execute([Str::slug($user_login, '_')]);

        if ($stmt->rowCount()) {
            $loginUser = new User($stmt->fetch());

            if ($loginUser->failed_login > 2) {
                if ($captchaCode) {
                    if (mb_strlen($captchaCode) > 2 && strtolower($captchaCode) === strtolower($_SESSION['code'])) {
                        // Если введен правильный проверочный код
                        $captcha = true;
                    } else {
                        // Если проверочный код указан неверно
                        $error[] = __('The security code is not correct');
                    }

                    unset($_SESSION['code']);
                } else {
                    // Показываем CAPTCHA
                    $display_form = 0;
                    $code = (string) new Mobicms\Captcha\Code();
                    $_SESSION['code'] = $code;
                    echo $view->render(
                        'login::captcha',
                        [
                            'captcha'    => new Mobicms\Captcha\Image($code),
                            'user_login' => $user_login,
                            'user_pass'  => $user_pass,
                            'id'         => $loginUser->id,
                        ]
                    );
                }
            }

            if ($loginUser->failed_login < 3 || $captcha) {
                if (md5(md5($user_pass)) == $loginUser->password) {
                    // Если логин удачный
                    $display_form = 0;
                    $db->exec("UPDATE `users` SET `failed_login` = '0' WHERE `id` = " . $loginUser->id);

                    if (! $loginUser->email_confirmed && $config['user_email_confirmation']) {
                        // Показываем сообщение о неподтвержденной регистрации
                        echo $view->render('login::confirm', ['confirm' => 'email']);
                    } elseif (! $loginUser->preg) {
                        // Показываем сообщение о неподтвержденной регистрации
                        echo $view->render('login::confirm', ['confirm' => 'moderation']);
                    } else {
                        // Если все проверки прошли удачно, подготавливаем вход на сайт
                        setcookie('cuid', (string) $loginUser->id, time() + 3600 * 24 * 365, '/');
                        setcookie('cups', md5($user_pass), time() + 3600 * 24 * 365, '/');

                        $db->exec("UPDATE `users` SET `sestime` = '" . time() . "' WHERE `id` = " . $loginUser->id);
                        header('Location: /');
                        exit;
                    }
                } else {
                    // Если логин неудачный
                    if ($loginUser->failed_login < 3) {
                        // Прибавляем к счетчику неудачных логинов
                        $failed_login = $loginUser->failed_login + 1;
                        $db->exec("UPDATE `users` SET `failed_login` = '" . $failed_login . "' WHERE `id` = " . $loginUser->id);
                    }

                    $error[] = __('Authorization failed');
                }
            }
        } else {
            $error[] = __('Authorization failed');
        }
    }

    if ($display_form) {
        // Показываем LOGIN форму
        echo $view->render(
            'login::login',
            [
                'error'      => isset($_POST['login']) ? $error : [],
                'user_login' => $user_login ?? '',
            ]
        );
    }
}
