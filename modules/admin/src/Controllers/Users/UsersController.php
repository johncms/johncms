<?php

/**
 * This file is part of JohnCMS Content Management System.
 *
 * @copyright JohnCMS Community
 * @license   https://opensource.org/licenses/GPL-3.0 GPL-3.0
 * @link      https://johncms.com JohnCMS Project
 */

declare(strict_types=1);

namespace Admin\Controllers\Users;

use Admin\Controllers\BaseAdminController;
use Illuminate\Support\Str;
use Johncms\System\Http\Request;
use Johncms\System\Users\User;
use Mobicms\Captcha\Code;
use Mobicms\Captcha\Image;

class UsersController extends BaseAdminController
{
    protected $module_name = 'admin';

    public function login(User $user, Request $request): string
    {
        if ($user->isValid()) {
            redirect('/admin/');
        }

        $this->render->addData(
            [
                'title'      => __('Login'),
                'page_title' => __('Login'),
            ]
        );

        $config = di('config')['johncms'];
        $db = di(\PDO::class);

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
                        $code = (string) new Code();
                        $_SESSION['code'] = $code;
                        return $this->render->render(
                            'admin::users/captcha',
                            [
                                'captcha'    => new Image($code),
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

                        if ((! $loginUser->email_confirmed && $config['user_email_confirmation']) || ! $loginUser->preg) {
                            redirect('/');
                        } else {
                            // Если все проверки прошли удачно, подготавливаем вход на сайт
                            setcookie('cuid', (string) $loginUser->id, time() + 3600 * 24 * 365, '/');
                            setcookie('cups', md5($user_pass), time() + 3600 * 24 * 365, '/');

                            $db->exec("UPDATE `users` SET `sestime` = '" . time() . "' WHERE `id` = " . $loginUser->id);
                            redirect('/admin/');
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
            return $this->render->render(
                'admin::users/login',
                [
                    'data' => [
                        'error'      => isset($_POST['login']) ? $error : [],
                        'user_login' => $user_login ?? '',
                    ],
                ]
            );
        }

        return '';
    }
}
