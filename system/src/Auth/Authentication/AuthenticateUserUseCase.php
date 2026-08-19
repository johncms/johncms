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
use Johncms\Auth\Events\AuthEventLoggerInterface;
use Johncms\Auth\Events\AuthEventType;
use Johncms\Auth\Password\PasswordHasherInterface;
use Johncms\Auth\Throttling\LoginThrottleInterface;
use Johncms\Captcha\CaptchaManager;
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
    /**
     * The captcha of the sign-in screens. Both of them — the public one and the panel — ask for
     * the same challenge, so it is named here rather than in either of them.
     */
    public const CAPTCHA_SCOPE = 'login';

    public function __construct(
        private CaptchaManager $captcha,
        private PasswordHasherInterface $hasher,
        private LoginThrottleInterface $throttle,
        private Environment $environment,
        private AuthEventLoggerInterface $eventLogger,
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

            $verification = $this->captcha->verify(
                $credentials->captchaAnswer,
                self::CAPTCHA_SCOPE,
                $this->environment->getClientInfo()->ip,
            );

            if (! $verification->passed) {
                $this->registerFailure($keys);
                $this->logFailure($credentials->login, 'captcha_mismatch');

                return new LoginResultDTO(LoginStatus::CaptchaMismatch);
            }
        }

        $user = $this->findUser($credentials->login);

        // An unknown login and a wrong password answer the same, so the form cannot be used to
        // find out which accounts exist.
        if ($user === null || ! $this->hasher->verify($credentials->password, $user->password)) {
            $this->registerFailure($keys);
            $this->logFailure($credentials->login, 'invalid_credentials', $user?->id);

            return new LoginResultDTO(LoginStatus::InvalidCredentials);
        }

        $this->rehashIfNeeded($user, $credentials->password);

        foreach ($keys as $key) {
            $this->throttle->clear($key);
        }

        // The password was right, so the account is named from here on: what follows is about
        // the state of the account, not about who is trying to get in.
        if (! $user->email_confirmed && config('johncms.user_email_confirmation')) {
            $this->logFailure($credentials->login, 'email_not_confirmed', $user->id);

            return new LoginResultDTO(LoginStatus::EmailNotConfirmed, $user->id);
        }

        if (! $user->preg) {
            $this->logFailure($credentials->login, 'moderation_pending', $user->id);

            return new LoginResultDTO(LoginStatus::ModerationPending, $user->id);
        }

        $user->update(['sestime' => time()]);
        $this->eventLogger->log(AuthEventType::LoginSuccess, $user->id);

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
     * A refused attempt, with the login that was tried and why it was refused: without the login
     * the trail cannot tell an owner mistyping their password from somebody walking through names.
     *
     * Attempts refused by the throttle are not recorded — they are the consequence of failures
     * already in the log, and writing a row for each of them would let anyone fill the table.
     */
    private function logFailure(string $login, string $reason, ?int $userId = null): void
    {
        $this->eventLogger->log(
            AuthEventType::LoginFailed,
            $userId,
            [
                'login'  => mb_substr(trim($login), 0, 191),
                'reason' => $reason,
            ]
        );
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
