<?php

declare(strict_types=1);

namespace Johncms\Modules\Profile\Application\UseCases;

use Johncms\Auth\Events\AuthEventLoggerInterface;
use Johncms\Auth\Events\AuthEventType;
use Johncms\Auth\Password\PasswordResetTokens;
use Johncms\Mail\EmailMessage;
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

        (new EmailMessage())->create(
            [
                'priority' => 1,
                'locale'   => $this->translator->getLocale(),
                'template' => '@theme/emails/restore-password.twig',
                'fields'   => [
                    'email_to'        => $user->mail,
                    'name_to'         => $name,
                    'subject'         => __('Password recovery'),
                    'user_name'       => $name,
                    'link_to_restore' => $link,
                ],
            ]
        );

        // Recorded even though nothing has changed yet: a wave of requests against one account is
        // what an attempted takeover looks like from the outside.
        $this->eventLogger->log(AuthEventType::PasswordResetRequested, $user->id);
    }
}
