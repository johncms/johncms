<?php

declare(strict_types=1);

namespace Johncms\Modules\Profile\Application\UseCases;

use Johncms\Auth\Password\PasswordResetTokens;
use Johncms\Mail\EmailMessage;
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

        (new EmailMessage())->create(
            [
                'priority' => 1,
                'locale'   => $this->translator->getLocale(),
                'template' => '@theme/emails/restore-password-complete.twig',
                'fields'   => [
                    'email_to'      => $user->mail,
                    'name_to'       => $name,
                    'subject'       => __('Your new password'),
                    'user_name'     => $name,
                    'user_login'    => $user->name,
                    'user_password' => $password,
                ],
            ]
        );

        $this->profileUserRepository->updatePassword($user->id, md5(md5($password)));
    }
}
