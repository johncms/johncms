<?php

/**
 * This file is part of JohnCMS Content Management System.
 *
 * @copyright JohnCMS Community
 * @license   https://opensource.org/licenses/GPL-3.0 GPL-3.0
 * @link      https://johncms.com JohnCMS Project
 */

declare(strict_types=1);

namespace Johncms\Auth\Authentication;

use Illuminate\Support\Str;
use Johncms\Auth\Password\PasswordHasherInterface;
use Johncms\Users\User;

/**
 * Checks whether the credentials are good enough to sign in with.
 *
 * Lives in the core rather than in the login module because it is not the login module's alone:
 * the admin panel has its own sign-in screen, and it has to work on a site where the public one
 * is switched off or absent entirely. Two screens, one decision — the alternative was the two
 * copies this replaces, one of them written in raw SQL.
 *
 * Does not open a session: what to do with a successful answer is the caller's business, and
 * only the caller knows where the visitor is going afterwards.
 */
final readonly class AuthenticateUserUseCase
{
    /** Failures after which the form starts asking for a verification code. */
    private const FAILURES_BEFORE_CAPTCHA = 3;

    public function __construct(
        private LoginCaptcha $captcha,
        private PasswordHasherInterface $hasher,
    ) {
    }

    public function execute(LoginCredentialsDTO $credentials): LoginResultDTO
    {
        $user = $this->findUser($credentials->login);

        // An unknown login and a wrong password answer the same, so the form cannot be used to
        // find out which accounts exist.
        if ($user === null) {
            return new LoginResultDTO(LoginStatus::InvalidCredentials);
        }

        if ($user->failed_login >= self::FAILURES_BEFORE_CAPTCHA) {
            if ($credentials->captchaAnswer === '') {
                return new LoginResultDTO(LoginStatus::CaptchaRequired);
            }

            if (! $this->captcha->verify($credentials->captchaAnswer)) {
                return new LoginResultDTO(LoginStatus::CaptchaMismatch);
            }
        }

        if (! $this->hasher->verify($credentials->password, $user->password)) {
            $this->countFailure($user);

            return new LoginResultDTO(LoginStatus::InvalidCredentials);
        }

        $this->rehashIfNeeded($user, $credentials->password);
        $user->update(['failed_login' => 0]);

        // The password was right, so the account is named from here on: what follows is about
        // the state of the account, not about who is trying to get in.
        if (! $user->email_confirmed && config('johncms.user_email_confirmation')) {
            return new LoginResultDTO(LoginStatus::EmailNotConfirmed, $user->id);
        }

        if (! $user->preg) {
            return new LoginResultDTO(LoginStatus::ModerationPending, $user->id);
        }

        $user->update(['sestime' => time()]);

        return new LoginResultDTO(LoginStatus::Success, $user->id);
    }

    /**
     * A successful sign-in is the only moment the password exists in the clear, so it is the
     * only moment a hash made by an older scheme can be replaced. Accounts move to the current
     * one as their owners come back, without anyone being asked to reset anything.
     */
    private function rehashIfNeeded(User $user, string $password): void
    {
        if ($this->hasher->needsRehash($user->password)) {
            $user->password = $this->hasher->hash($password);
            $user->save();
        }
    }

    private function findUser(string $login): ?User
    {
        $nameLat = Str::slug($login, '_');

        if ($nameLat === '') {
            return null;
        }

        return User::query()->where('name_lat', '=', $nameLat)->first();
    }

    /**
     * The counter stops at the threshold: past it the form asks for a verification code anyway,
     * and letting it climb would only make the number in the database meaningless.
     */
    private function countFailure(User $user): void
    {
        if ($user->failed_login < self::FAILURES_BEFORE_CAPTCHA) {
            $user->update(['failed_login' => $user->failed_login + 1]);
        }
    }
}
