<?php

declare(strict_types=1);

namespace Johncms\Modules\Profile\Application\UseCases;

use Johncms\Mail\EmailMessage;
use Johncms\Modules\Profile\Application\DTO\SendRecoveryCommand;
use Johncms\Modules\Profile\Application\Exceptions\PasswordRecoveryException;
use Johncms\Modules\Profile\Domain\Repository\ProfileUserRepositoryInterface;
use Johncms\System\i18n\Translator;
use Johncms\Utils\Transliterator;

final readonly class SendPasswordRecoveryUseCase
{
    private const RECOVERY_INTERVAL = 86400;

    public function __construct(
        private ProfileUserRepositoryInterface $profileUserRepository,
        private Translator $translator,
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

        if ($user->rest_time > time() - self::RECOVERY_INTERVAL) {
            throw new PasswordRecoveryException(__('Password can be recovered 1 time per day'));
        }

        $code = md5((string) random_int(1000, 9999));
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

        $this->profileUserRepository->startPasswordRecovery($user->id, $code, time());
    }
}
