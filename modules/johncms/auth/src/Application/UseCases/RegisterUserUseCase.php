<?php

declare(strict_types=1);

namespace Johncms\Modules\Auth\Application\UseCases;

use Johncms\Auth\Password\PasswordHasherInterface;
use Johncms\Auth\RegistrationSettings;
use Johncms\Auth\SecureToken;
use Johncms\Mail\Queue\MailQueueInterface;
use Johncms\Mail\Queue\QueuedEmailDTO;
use Johncms\Modules\Auth\Application\DTO\RegistrationFormDTO;
use Johncms\Security\ClientInfoDTO;
use Johncms\System\i18n\Translator;
use Johncms\Users\User;

final readonly class RegisterUserUseCase
{
    public function __construct(
        private RegistrationSettings $settings,
        private Translator $translator,
        private PasswordHasherInterface $hasher,
        private MailQueueInterface $mailQueue,
    ) {
    }

    public function execute(RegistrationFormDTO $dto, ClientInfoDTO $clientInfo): User
    {
        $config = config('johncms');

        $newUser = new User();
        $newUser->fill(
            [
                'name'              => $dto->name,
                'name_lat'          => $dto->nameLat,
                'imname'            => $dto->imname,
                'about'             => $dto->about,
                'sex'               => $dto->sex,
                'mail'              => $dto->email,
                'ip'                => $clientInfo->ip,
                'ip_via_proxy'      => $clientInfo->ipViaProxy,
                'browser'           => $clientInfo->userAgent,
                'datereg'           => time(),
                'lastdate'          => time(),
                'sestime'           => time(),
                'preg'              => $this->settings->moderationEnabled() ? 0 : 1,
                'set_user'          => [],
                'set_forum'         => [],
                'set_mail'          => [],
                'smileys'           => [],
                'email_confirmed'   => ! empty($config['user_email_confirmation']) ? null : 1,
                'confirmation_code' => ! empty($config['user_email_confirmation']) ? SecureToken::generate() : null,
            ]
        );
        // Assigned rather than filled: the password is not a mass-assignable attribute, so that
        // no form handler can ever set it by accident.
        $newUser->password = $this->hasher->hash($dto->password);
        $newUser->save();

        if ($config['user_email_confirmation']) {
            $link = $config['homeurl'] . '/registration/confirm-email?id=' . $newUser->id . '&code=' . $newUser->confirmation_code;
            $name = ! empty($newUser->imname) ? $newUser->imname : $newUser->name;
            $this->mailQueue->push(
                new QueuedEmailDTO(
                    template: '@theme/emails/registration.twig',
                    locale: $this->translator->getLocale(),
                    recipient: $newUser->mail,
                    recipientName: $name,
                    subject: __('Registration on the website'),
                    priority: 1,
                    variables: [
                        'user_name'       => $name,
                        'user_login'      => $newUser->name,
                        'link_to_confirm' => $link,
                    ],
                )
            );
        }

        return $newUser;
    }
}
