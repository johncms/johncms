<?php

declare(strict_types=1);

namespace Johncms\Modules\Admin\Application\Controllers\Users;

use Illuminate\Support\Str;
use Johncms\Http\Controller\AdminControllerContext;
use Johncms\Http\Request;
use Johncms\Http\Session;
use Johncms\Http\View\ViewResponse;
use Johncms\System\Users\User;
use Mobicms\Captcha\Code;
use Mobicms\Captcha\Image;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;

final readonly class UsersController
{
    public function __construct(
        private AdminControllerContext $controllerContext,
        private Session $session,
    ) {
        $this->controllerContext->initModule('admin');
    }

    public function login(Request $request, User $user): Response | ViewResponse
    {
        if ($user->isValid()) {
            redirect('/admin/');
        }

        $config = config('johncms');
        $db = di(\PDO::class);

        $error = [];
        $captcha = false;
        $display_form = 1;
        $user_login = trim($request->body('n', ''));
        $user_pass = trim($request->body('p', ''));
        $captchaCode = trim($request->body('code', ''));

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
                        $sessionCode = $this->session->get('code');
                        if (mb_strlen($captchaCode) > 2 && strtolower($captchaCode) === strtolower($sessionCode)) {
                            // Если введен правильный проверочный код
                            $captcha = true;
                        } else {
                            // Если проверочный код указан неверно
                            $error[] = __('The security code is not correct');
                        }

                        $this->session->remove('code');
                    } else {
                        // Показываем CAPTCHA
                        $code = (string) new Code();
                        $this->session->set('code', $code);
                        return new ViewResponse(
                            '@admin/login-captcha.twig',
                            $this->pageMeta() + [
                                'captcha'    => (string) new Image($code),
                                'user_login' => $user_login,
                                'user_pass'  => $user_pass,
                                'id'         => $loginUser->id,
                                'remember'   => $request->body('mem', ''),
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
                            $expire = time() + 3600 * 24 * 365;
                            $response = new RedirectResponse('/admin/');
                            $response->headers->setCookie(
                                Cookie::create('cuid', (string) $loginUser->id, $expire, '/', null, false, false, false, null)
                            );
                            $response->headers->setCookie(
                                Cookie::create('cups', md5($user_pass), $expire, '/', null, false, false, false, null)
                            );

                            $db->exec("UPDATE `users` SET `sestime` = '" . time() . "' WHERE `id` = " . $loginUser->id);
                            return $response;
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
            return new ViewResponse(
                '@admin/login.twig',
                $this->pageMeta() + [
                    'errors'     => $request->hasBody('login') ? $error : [],
                    'user_login' => $user_login,
                ]
            );
        }

        return new Response('');
    }

    /**
     * @return array<string, string>
     */
    private function pageMeta(): array
    {
        return [
            'title'      => __('Login'),
            'page_title' => __('Login'),
        ];
    }
}
