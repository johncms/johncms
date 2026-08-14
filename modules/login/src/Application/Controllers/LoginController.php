<?php

declare(strict_types=1);

namespace Johncms\Modules\Login\Application\Controllers;

use Johncms\Auth\Authentication\AuthenticateUserUseCase;
use Johncms\Auth\Authentication\LoginCaptcha;
use Johncms\Auth\Authentication\LoginCredentialsDTO;
use Johncms\Auth\Authentication\LoginStatus;
use Johncms\Auth\Session\SignInManager;
use Johncms\Http\Request;
use Johncms\Http\View\ViewResponse;
use Johncms\NavChain;
use Mobicms\Captcha\Image;
use Symfony\Component\HttpFoundation\RedirectResponse;

final readonly class LoginController
{
    public function __construct(
        private NavChain $navChain,
        private AuthenticateUserUseCase $authenticateUser,
        private LoginCaptcha $captcha,
        private SignInManager $signInManager,
    ) {
    }

    public function __invoke(Request $request): RedirectResponse|ViewResponse
    {
        $this->navChain->add(__('Login'));

        $userLogin = $request->body('n', '');
        $userPass = $request->body('p', '');

        if (! $request->hasBody('login')) {
            return $this->loginForm([], $userLogin, $request);
        }

        $errors = $this->missingFields($userLogin, $userPass);

        if ($errors !== []) {
            return $this->loginForm($errors, $userLogin, $request);
        }

        $result = $this->authenticateUser->execute(
            new LoginCredentialsDTO($userLogin, $userPass, $request->body('code', ''))
        );

        return match ($result->status) {
            LoginStatus::Success => $this->handleSuccess($request, (int) $result->userId),
            LoginStatus::CaptchaRequired => $this->captchaForm($request, $userLogin, $userPass),
            LoginStatus::CaptchaMismatch => $this->loginForm(
                [__('The security code is not correct')],
                $userLogin,
                $request
            ),
            LoginStatus::TooManyAttempts => $this->loginForm(
                [sprintf(__('Too many attempts. Try again in %s.'), $this->waitTime($result->retryAfter))],
                $userLogin,
                $request
            ),
            LoginStatus::EmailNotConfirmed => $this->confirmationRequired('email'),
            LoginStatus::ModerationPending => $this->confirmationRequired('moderation'),
            LoginStatus::InvalidCredentials => $this->loginForm([__('Authorization failed')], $userLogin, $request),
        };
    }

    /**
     * @return array<int, string>
     */
    private function missingFields(string $userLogin, string $userPass): array
    {
        $errors = [];

        if ($userLogin === '') {
            $errors[] = __('You have not entered login');
        }

        if ($userPass === '') {
            $errors[] = __('You have not entered password');
        }

        return $errors;
    }

    /**
     * The wait in whole minutes, rounded up: telling somebody to come back in 47 seconds invites
     * them to sit and count, and the exact figure is of no use to them.
     */
    private function waitTime(int $seconds): string
    {
        $minutes = max(1, (int) ceil($seconds / 60));

        return sprintf(n__('%d minute', '%d minutes', $minutes), $minutes);
    }

    private function handleSuccess(Request $request, int $userId): RedirectResponse
    {
        $this->signInManager->signIn($userId, $this->remembered($request), $request);

        return new RedirectResponse('/');
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

    private function captchaForm(Request $request, string $userLogin, string $userPass): ViewResponse
    {
        return new ViewResponse(
            '@login/public/captcha.twig',
            [
                'title'      => __('Login'),
                'page_title' => __('Login'),
                'captcha'    => new Image($this->captcha->issue()),
                'user_login' => $userLogin,
                'user_pass'  => $userPass,
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
