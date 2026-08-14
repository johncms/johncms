<?php

declare(strict_types=1);

namespace Johncms\Modules\Login\Application\Controllers;

use Johncms\Auth\Session\SignInManager;
use Johncms\Modules\Login\Application\UseCases\PerformLoginUseCase;
use Johncms\Modules\Login\Domain\Enums\LoginStatus;
use Johncms\NavChain;
use Johncms\Http\Request;
use Johncms\Http\View\ViewResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;

final readonly class LoginController
{
    public function __construct(
        private NavChain $navChain,
        private PerformLoginUseCase $performLogin,
        private SignInManager $signInManager,
    ) {
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
                    LoginStatus::Success => $this->handleSuccess($request, (int) $result->userId),
                    LoginStatus::CaptchaRequired => new ViewResponse(
                        '@login/public/captcha.twig',
                        [
                            'title'      => __('Login'),
                            'page_title' => __('Login'),
                            'captcha'    => $result->captcha,
                            'user_login' => $userLogin,
                            'user_pass'  => $userPass,
                            'remember'   => $this->remembered($request),
                        ]
                    ),
                    LoginStatus::EmailNotConfirmed => $this->confirmationRequired('email'),
                    LoginStatus::ModerationPending => $this->confirmationRequired('moderation'),
                    LoginStatus::Error => $this->loginForm($result->errors, $userLogin, $request),
                };
            }
        }

        return $this->loginForm($error, $userLogin, $request);
    }

    /**
     * @param array<int, string> $errors
     */
    private function loginForm(array $errors, string $userLogin, Request $request): ViewResponse
    {
        return new ViewResponse(
            '@login/public/login.twig',
            [
                'title'      => __('Login'),
                'page_title' => __('Login'),
                'error'      => $errors,
                'user_login' => $userLogin,
                'remember'   => $this->remembered($request),
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

    private function handleSuccess(Request $request, int $userId): RedirectResponse
    {
        $this->signInManager->signIn($userId, $this->remembered($request), $request);

        return new RedirectResponse('/');
    }

    /**
     * Whether to keep the visitor signed in after the browser closes. The checkbox is pre-checked
     * by default, so an absent field on a submitted form means it was cleared on purpose.
     */
    private function remembered(Request $request): bool
    {
        if (! $request->hasBody('login')) {
            return (bool) config('auth.session.remember_by_default', true);
        }

        return $request->hasBody('remember');
    }
}
