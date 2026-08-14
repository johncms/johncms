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
use Johncms\Auth\Throttling\LoginThrottleInterface;
use Johncms\Http\Environment;
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
    public function __construct(
        private LoginCaptcha $captcha,
        private PasswordHasherInterface $hasher,
        private LoginThrottleInterface $throttle,
        private Environment $environment,
    ) {
    }

    public function execute(LoginCredentialsDTO $credentials): LoginResultDTO
    {
        $keys = $this->throttleKeys($credentials->login);

        foreach ($keys as $key) {
            $retryAfter = $this->throttle->retryAfter($key);

            if ($retryAfter > 0) {
                return new LoginResultDTO(LoginStatus::TooManyAttempts, retryAfter: $retryAfter);
            }
        }

        // Asked for before the password is looked at, and decided on the attempts rather than on
        // the account: a wrong password does not have to name an existing account to be counted.
        if ($this->verificationNeeded($keys)) {
            if ($credentials->captchaAnswer === '') {
                return new LoginResultDTO(LoginStatus::CaptchaRequired);
            }

            if (! $this->captcha->verify($credentials->captchaAnswer)) {
                $this->registerFailure($keys);

                return new LoginResultDTO(LoginStatus::CaptchaMismatch);
            }
        }

        $user = $this->findUser($credentials->login);

        // An unknown login and a wrong password answer the same, so the form cannot be used to
        // find out which accounts exist.
        if ($user === null || ! $this->hasher->verify($credentials->password, $user->password)) {
            $this->registerFailure($keys);

            return new LoginResultDTO(LoginStatus::InvalidCredentials);
        }

        $this->rehashIfNeeded($user, $credentials->password);

        foreach ($keys as $key) {
            $this->throttle->clear($key);
        }

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
     * The login being tried and the address trying it, counted separately: neither walking
     * through logins from one address nor rotating addresses against one login gets around the
     * limit, and a guesser cannot lock the owner of an account out by failing on their behalf —
     * their own address runs out of attempts first.
     *
     * @return list<string>
     */
    private function throttleKeys(string $login): array
    {
        return [
            'login:' . mb_strtolower(trim($login)),
            'ip:' . $this->environment->getClientInfo()->ip,
        ];
    }

    /**
     * @param list<string> $keys
     */
    private function verificationNeeded(array $keys): bool
    {
        foreach ($keys as $key) {
            if ($this->throttle->requiresVerification($key)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param list<string> $keys
     */
    private function registerFailure(array $keys): void
    {
        foreach ($keys as $key) {
            $this->throttle->registerFailure($key);
        }
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
}
