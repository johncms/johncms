<?php

declare(strict_types=1);

namespace Johncms\Modules\Profile\Application\UseCases;

use Johncms\Auth\Events\AuthEventLoggerInterface;
use Johncms\Auth\Events\AuthEventType;
use Johncms\Auth\Password\PasswordResetTokens;
use Johncms\Mail\Queue\MailQueueInterface;
use Johncms\Mail\Queue\QueuedEmailDTO;
use Johncms\Modules\Profile\Application\DTO\SendRecoveryCommand;
use Johncms\Modules\Profile\Application\Exceptions\PasswordRecoveryException;
use Johncms\Modules\Profile\Domain\Repository\ProfileUserRepositoryInterface;
use Johncms\System\i18n\Translator;
use Johncms\Utils\Transliterator;

final readonly class SendPasswordRecoveryUseCase
{
    public function __construct(
        private ProfileUserRepositoryInterface $profileUserRepository,
        private Translator $translator,
        private PasswordResetTokens $resetTokens,
        private AuthEventLoggerInterface $eventLogger,
        private MailQueueInterface $mailQueue,
    ) {
    }

    public function execute(SendRecoveryCommand $command, string $homeUrl): void
    {
        $nick = Transliterator::toLatin($command->nick);
        $email = trim($command->email);

        if (! $nick || ! $email) {
            throw new PasswordRecoveryException(__('The required fields are not filled'));
        }

        $user = $this->profileUserRepository->findByNameLat($nick);
        if ($user === null) {
            throw new PasswordRecoveryException(__('User does not exists'));
        }

        if (empty($user->mail) || $user->mail !== $email) {
            throw new PasswordRecoveryException(__('Invalid Email address'));
        }

        if (! $this->resetTokens->canRequest($user->id)) {
            throw new PasswordRecoveryException(__('Password can be recovered 1 time per day'));
        }

        $code = $this->resetTokens->issue($user->id);
        $link = $homeUrl . '/profile/password-recovery/set/' . $user->id . '/' . $code;
        $name = ! empty($user->imname) ? $user->imname : $user->name;

        $this->mailQueue->push(
            new QueuedEmailDTO(
                template: '@theme/emails/restore-password.twig',
                locale: $this->translator->getLocale(),
                recipient: $user->mail,
                recipientName: $name,
                subject: __('Password recovery'),
                priority: 1,
                variables: [
                    'user_name'       => $name,
                    'link_to_restore' => $link,
                ],
            )
        );

        // Recorded even though nothing has changed yet: a wave of requests against one account is
        // what an attempted takeover looks like from the outside.
        $this->eventLogger->log(AuthEventType::PasswordResetRequested, $user->id);
    }
}
