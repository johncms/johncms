<?php

declare(strict_types=1);

namespace Johncms\Modules\Login\Application\Controllers;

use Johncms\Http\Controller\ControllerContext;
use Johncms\Modules\Login\Application\UseCases\PerformLoginUseCase;
use Johncms\Modules\Login\Domain\Enums\LoginStatus;
use Johncms\NavChain;
use Johncms\Http\Request;
use Johncms\Http\View\ViewResponse;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;

final readonly class LoginController
{
    public function __construct(
        private ControllerContext $controllerContext,
        private NavChain $navChain,
        private PerformLoginUseCase $performLogin,
    ) {
        $this->controllerContext->initModule('login');
    }

    public function __invoke(Request $request): RedirectResponse|ViewResponse
    {
        $this->navChain->add(__('Login'));

        $error = [];
        $userLogin = $request->body('n', '');
        $userPass = $request->body('p', '');

        if ($request->hasBody('login')) {
            if (empty($userLogin)) {
                $error[] = __('You have not entered login');
            }

            if (empty($userPass)) {
                $error[] = __('You have not entered password');
            }

            if (! $error) {
                $captchaCode = $request->body('code', '');
                $result = $this->performLogin->execute($userLogin, $userPass, $captchaCode);

                return match ($result->status) {
                    LoginStatus::Success => $this->handleSuccess($result->userId, $result->passwordHash),
                    LoginStatus::CaptchaRequired => new ViewResponse(
                        '@login/public/captcha.twig',
                        [
                            'title'      => __('Login'),
                            'page_title' => __('Login'),
                            'captcha'    => $result->captcha,
                            'user_login' => $userLogin,
                            'user_pass'  => $userPass,
                        ]
                    ),
                    LoginStatus::EmailNotConfirmed => $this->confirmationRequired('email'),
                    LoginStatus::ModerationPending => $this->confirmationRequired('moderation'),
                    LoginStatus::Error => $this->loginForm($result->errors, $userLogin),
                };
            }
        }

        return $this->loginForm($error, $userLogin);
    }

    /**
     * @param array<int, string> $errors
     */
    private function loginForm(array $errors, string $userLogin): ViewResponse
    {
        return new ViewResponse(
            '@login/public/login.twig',
            [
                'title'      => __('Login'),
                'page_title' => __('Login'),
                'error'      => $errors,
                'user_login' => $userLogin,
            ]
        );
    }

    private function confirmationRequired(string $type): ViewResponse
    {
        return new ViewResponse(
            '@login/public/confirm.twig',
            [
                'title'      => __('Login'),
                'page_title' => __('Login'),
                'confirm'    => $type,
            ]
        );
    }

    private function handleSuccess(?int $userId, ?string $passwordHash): RedirectResponse
    {
        $response = new RedirectResponse('/');
        $expire = time() + 3600 * 24 * 365;
        $response->headers->setCookie(Cookie::create('cuid', (string) $userId, $expire, '/', null, false, false, false, null));
        $response->headers->setCookie(Cookie::create('cups', (string) $passwordHash, $expire, '/', null, false, false, false, null));
        return $response;
    }
}
