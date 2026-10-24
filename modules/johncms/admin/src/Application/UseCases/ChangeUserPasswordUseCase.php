<?php

declare(strict_types=1);

namespace Johncms\Modules\Admin\Application\UseCases;

use Johncms\Auth\CurrentUser;
use Johncms\Auth\Events\AuthEventLoggerInterface;
use Johncms\Auth\Events\AuthEventType;
use Johncms\Auth\Password\PasswordHasherInterface;
use Johncms\Auth\Session\AuthSessionManager;
use Johncms\Auth\Session\SessionRevocationReason;
use Johncms\Auth\Session\SignInManager;
use Johncms\Modules\Admin\Application\Exceptions\UserEditException;
use Johncms\Modules\Admin\Domain\Repository\UserEditRepositoryInterface;
use Johncms\Users\User;
use Johncms\Validator\Rules\StringLength;
use Johncms\Validator\ValidatorInterface;

/**
 * Gives an account a new password from the panel. The rules are those of registration, and the
 * account may be signed out everywhere at the same time: a password is changed by the staff most
 * often because somebody else knows the old one.
 *
 * The owner changing their own is asked for the current one, as the settings of the account ask,
 * and stays signed in where they are.
 */
final readonly class ChangeUserPasswordUseCase
{
    public function __construct(
        private UserEditRepositoryInterface $users,
        private PasswordHasherInterface $hasher,
        private AuthSessionManager $sessions,
        private SignInManager $signIn,
        private CurrentUser $currentUser,
        private AuthEventLoggerInterface $events,
        private ValidatorInterface $validator,
    ) {
    }

    public function execute(User $user, string $currentPassword, string $password, string $confirmation, bool $signOut): void
    {
        $self = $user->id === $this->currentUser->id();
        $result = $this->validator->validate(['password' => $password], ['password' => [new StringLength(min: 6)]]);

        if ($self && ! $this->hasher->verify($currentPassword, $user->password)) {
            $result = $result->withError('current_password', __('Old password entered incorrectly'));
        }

        if ($password !== $confirmation) {
            $result = $result->withError('confirmation', __('The password confirmation you entered is wrong'));
        }

        $result->throwIfInvalid(static fn (array $errors): UserEditException => new UserEditException($errors));

        $this->users->updatePassword($user->id, $this->hasher->hash($password));
        // The staff changing the password and the owner changing it are the same event, told
        // apart by the actor the logger fills in.
        $this->events->log(AuthEventType::PasswordChanged, $user->id);

        if (! $signOut) {
            return;
        }

        if ($self) {
            $this->signIn->signOutEverywhereElse($user->id, SessionRevocationReason::PasswordChange);
        } else {
            $this->sessions->revokeAllFor($user->id, SessionRevocationReason::Admin);
        }
    }
}
