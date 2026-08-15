<?php

declare(strict_types=1);

namespace Johncms\Modules\Admin\Application\Controllers\Users;

use Johncms\Auth\Authentication\AuthenticateUserUseCase;
use Johncms\Auth\Authentication\LoginCaptcha;
use Johncms\Auth\Authentication\LoginCredentialsDTO;
use Johncms\Auth\Authentication\LoginStatus;
use Johncms\Auth\AuthMethod;
use Johncms\Auth\Authorization\AccessCheckerInterface;
use Johncms\Auth\Authorization\CorePermissions;
use Johncms\Auth\Authorization\PermissionResolver;
use Johncms\Auth\CurrentUser;
use Johncms\Auth\Identity;
use Johncms\Auth\Session\SignInManager;
use Johncms\Http\Request;
use Johncms\Http\View\ViewResponse;
use Johncms\Users\User;
use Mobicms\Captcha\Image;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;

/**
 * The sign-in screen of the admin panel.
 *
 * It stays separate from the public one: a site may have no public sign-in at all — the theme
 * may not offer one, the module may be switched off — and an administrator still has to get in.
 * Separate is the screen, not the logic: the decision comes from the same core use case, and
 * only what happens around it differs.
 */
final readonly class UsersController
{
    public function __construct(
        private AuthenticateUserUseCase $authenticateUser,
        private LoginCaptcha $captcha,
        private SignInManager $signInManager,
        private PermissionResolver $permissionResolver,
        private AccessCheckerInterface $accessChecker,
    ) {
    }

    public function login(Request $request, CurrentUser $user): Response|ViewResponse
    {
        if ($user->isValid()) {
            redirect('/admin/');
        }

        $userLogin = trim($request->body('n', ''));
        $userPass = trim($request->body('p', ''));

        if (! $request->hasBody('login')) {
            return $this->loginForm([], $userLogin);
        }

        $errors = $this->missingFields($userLogin, $userPass);

        if ($errors !== []) {
            return $this->loginForm($errors, $userLogin);
        }

        $result = $this->authenticateUser->execute(
            new LoginCredentialsDTO($userLogin, $userPass, trim($request->body('code', '')))
        );

        return match ($result->status) {
            LoginStatus::Success => $this->handleSuccess($request, (int) $result->userId, $userLogin),
            LoginStatus::CaptchaRequired => $this->captchaForm($request, $userLogin, $userPass),
            LoginStatus::CaptchaMismatch => $this->loginForm([__('The security code is not correct')], $userLogin),
            LoginStatus::TooManyAttempts => $this->loginForm(
                [sprintf(__('Too many attempts. Try again in %s.'), $this->waitTime($result->retryAfter))],
                $userLogin
            ),
            // An account still waiting for its address to be confirmed or for approval has no
            // business here, and saying which of the two it is would confirm the login exists.
            LoginStatus::EmailNotConfirmed,
            LoginStatus::ModerationPending,
            LoginStatus::InvalidCredentials => $this->loginForm([__('Authorization failed')], $userLogin),
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
     * The wait in whole minutes, rounded up: the exact number of seconds is of no use to anyone
     * waiting it out.
     */
    private function waitTime(int $seconds): string
    {
        $minutes = max(1, (int) ceil($seconds / 60));

        return sprintf(n__('%d minute', '%d minutes', $minutes), $minutes);
    }

    /**
     * Correct credentials are not the same as being allowed in here. Answering on this screen,
     * instead of signing the visitor in and letting the panel refuse them afterwards, keeps the
     * message where it makes sense.
     */
    private function handleSuccess(Request $request, int $userId, string $userLogin): Response|ViewResponse
    {
        if (! $this->mayEnterAdminPanel($userId)) {
            return $this->loginForm([__('Access denied')], $userLogin, Response::HTTP_FORBIDDEN);
        }

        $this->signInManager->signIn($userId, $request->hasBody('mem'), $request);

        return new RedirectResponse('/admin/');
    }

    /**
     * Asked about the account that just proved who it is, not about the visitor of this request:
     * nobody is signed in yet, so the roles of that account are resolved on the spot.
     */
    private function mayEnterAdminPanel(int $userId): bool
    {
        $identity = $this->permissionResolver->resolve(new Identity(userId: $userId, method: AuthMethod::Session));

        return $this->accessChecker->allowsFor($identity, CorePermissions::ADMIN_ACCESS);
    }

    /**
     * @param array<int, string> $errors
     */
    private function loginForm(array $errors, string $userLogin, int $status = Response::HTTP_OK): ViewResponse
    {
        return new ViewResponse(
            '@admin/login.twig',
            $this->pageMeta() + [
                'errors'     => $errors,
                'user_login' => $userLogin,
            ],
            $status
        );
    }

    private function captchaForm(Request $request, string $userLogin, string $userPass): ViewResponse
    {
        return new ViewResponse(
            '@admin/login-captcha.twig',
            $this->pageMeta() + [
                'captcha'    => (string) new Image($this->captcha->issue()),
                'user_login' => $userLogin,
                'user_pass'  => $userPass,
                'remember'   => $request->hasBody('mem'),
            ]
        );
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
