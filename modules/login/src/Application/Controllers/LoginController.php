<?php

declare(strict_types=1);

namespace Johncms\Modules\Login\Application\Controllers;

use Johncms\Http\Controller\ControllerContext;
use Johncms\Modules\Login\Application\UseCases\PerformLoginUseCase;
use Johncms\Modules\Login\Domain\Enums\LoginStatus;
use Johncms\NavChain;
use Johncms\Http\Request;
use Johncms\System\View\Render;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;

final readonly class LoginController
{
    public function __construct(
        private ControllerContext $controllerContext,
        private Render $render,
        private Request $request,
        private NavChain $navChain,
        private PerformLoginUseCase $performLogin,
    ) {
        $this->controllerContext->initModule('login');
    }

    public function __invoke(): Response
    {
        $this->navChain->add(__('Login'));

        $error = [];
        $userLogin = $this->request->body('n', '');
        $userPass = $this->request->body('p', '');

        if ($this->request->hasBody('login')) {
            if (empty($userLogin)) {
                $error[] = __('You have not entered login');
            }

            if (empty($userPass)) {
                $error[] = __('You have not entered password');
            }

            if (! $error) {
                $captchaCode = $this->request->body('code', '');
                $result = $this->performLogin->execute($userLogin, $userPass, $captchaCode);

                return match ($result->status) {
                    LoginStatus::Success => $this->handleSuccess($result->userId, $result->passwordHash),
                    LoginStatus::CaptchaRequired => new Response($this->render->render(
                        'login::captcha',
                        [
                            'captcha'    => $result->captcha,
                            'user_login' => $userLogin,
                            'user_pass'  => $userPass,
                        ]
                    )),
                    LoginStatus::EmailNotConfirmed => new Response($this->render->render('login::confirm', ['confirm' => 'email'])),
                    LoginStatus::ModerationPending => new Response($this->render->render('login::confirm', ['confirm' => 'moderation'])),
                    LoginStatus::Error => new Response($this->render->render(
                        'login::login',
                        ['error' => $result->errors, 'user_login' => $userLogin]
                    )),
                };
            }
        }

        return new Response($this->render->render(
            'login::login',
            ['error' => $error, 'user_login' => $userLogin]
        ));
    }

    private function handleSuccess(?int $userId, ?string $passwordHash): Response
    {
        $response = new RedirectResponse('/');
        $expire = time() + 3600 * 24 * 365;
        $response->headers->setCookie(Cookie::create('cuid', (string) $userId, $expire, '/', null, false, false, false, null));
        $response->headers->setCookie(Cookie::create('cups', (string) $passwordHash, $expire, '/', null, false, false, false, null));
        return $response;
    }
}
