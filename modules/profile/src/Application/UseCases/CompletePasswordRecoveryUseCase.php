<?php

declare(strict_types=1);

namespace Johncms\Modules\Profile\Application\UseCases;

use Johncms\Auth\Events\AuthEventLoggerInterface;
use Johncms\Auth\Events\AuthEventType;
use Johncms\Auth\Password\PasswordHasherInterface;
use Johncms\Auth\Password\PasswordResetTokens;
use Johncms\Auth\Session\AuthSessionManager;
use Johncms\Auth\Session\SessionRevocationReason;
use Johncms\Mail\Queue\MailQueueInterface;
use Johncms\Mail\Queue\QueuedEmailDTO;
use Johncms\Modules\Profile\Application\Exceptions\PasswordRecoveryException;
use Johncms\Modules\Profile\Application\Services\PasswordGenerator;
use Johncms\Modules\Profile\Domain\Repository\ProfileUserRepositoryInterface;
use Johncms\System\i18n\Translator;
use Johncms\Users\User;

final readonly class CompletePasswordRecoveryUseCase
{
    public function __construct(
        private ProfileUserRepositoryInterface $profileUserRepository,
        private Translator $translator,
        private PasswordGenerator $passwordGenerator,
        private PasswordResetTokens $resetTokens,
        private PasswordHasherInterface $hasher,
        private AuthSessionManager $sessions,
        private AuthEventLoggerInterface $eventLogger,
        private MailQueueInterface $mailQueue,
    ) {
    }

    /**
     * Spends the recovery link and issues a new password. Consuming the token first is what
     * makes a resubmitted link a no-op instead of a second password reset.
     */
    public function execute(User $user, string $code): void
    {
        if ($this->resetTokens->consume($code) !== $user->id) {
            throw new PasswordRecoveryException(__('Time allotted for the password recovery has been exceeded'));
        }

        $password = $this->passwordGenerator->generate(4);
        $name = ! empty($user->imname) ? $user->imname : $user->name;

        $this->mailQueue->push(
            new QueuedEmailDTO(
                template: '@theme/emails/restore-password-complete.twig',
                locale: $this->translator->getLocale(),
                recipient: $user->mail,
                recipientName: $name,
                subject: __('Your new password'),
                priority: 1,
                variables: [
                    'user_name'     => $name,
                    'user_login'    => $user->name,
                    'user_password' => $password,
                ],
            )
        );

        $this->profileUserRepository->updatePassword($user->id, $this->hasher->hash($password));

        // Every session goes, without the exception a password change makes for the current one:
        // whoever is recovering a password is not signed in, so a session that survives here would
        // belong to whoever took the account over.
        $this->sessions->revokeAllFor($user->id, SessionRevocationReason::PasswordChange);
        $this->eventLogger->log(AuthEventType::PasswordResetCompleted, $user->id);
    }
}
