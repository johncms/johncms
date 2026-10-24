<?php

declare(strict_types=1);

namespace Johncms\Modules\Admin\Application\UseCases;

use Johncms\Auth\Events\AuthEventLoggerInterface;
use Johncms\Auth\Events\AuthEventType;
use Johncms\Auth\Password\PasswordHasherInterface;
use Johncms\Auth\Session\AuthSessionManager;
use Johncms\Auth\Session\SessionRevocationReason;
use Johncms\Modules\Admin\Application\Exceptions\UserEditException;
use Johncms\Modules\Admin\Domain\Repository\UserEditRepositoryInterface;
use Johncms\Users\User;
use Johncms\Validator\Rules\StringLength;
use Johncms\Validator\ValidatorInterface;

/**
 * Gives an account a new password from the panel. The rules are those of registration, and the
 * account may be signed out everywhere at the same time: a password is changed by the staff most
 * often because somebody else knows the old one.
 */
final readonly class ChangeUserPasswordUseCase
{
    public function __construct(
        private UserEditRepositoryInterface $users,
        private PasswordHasherInterface $hasher,
        private AuthSessionManager $sessions,
        private AuthEventLoggerInterface $events,
        private ValidatorInterface $validator,
    ) {
    }

    public function execute(User $user, string $password, string $confirmation, bool $signOut): void
    {
        $result = $this->validator->validate(['password' => $password], ['password' => [new StringLength(min: 6)]]);

        if ($password !== $confirmation) {
            $result = $result->withError('confirmation', __('The password confirmation you entered is wrong'));
        }

        $result->throwIfInvalid(static fn (array $errors): UserEditException => new UserEditException($errors));

        $this->users->updatePassword($user->id, $this->hasher->hash($password));
        // The staff changing the password and the owner changing it are the same event, told
        // apart by the actor the logger fills in.
        $this->events->log(AuthEventType::PasswordChanged, $user->id);

        if ($signOut) {
            $this->sessions->revokeAllFor($user->id, SessionRevocationReason::Admin);
        }
    }
}
