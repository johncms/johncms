<?php

declare(strict_types=1);

namespace Johncms\Modules\Registration\Application\UseCases;

use Johncms\Mail\EmailMessage;
use Johncms\Modules\Registration\Application\DTO\RegistrationFormDTO;
use Johncms\System\Http\Environment;
use Johncms\System\i18n\Translator;
use Johncms\Users\User;

final readonly class RegisterUserUseCase
{
    public function __construct(
        private Environment $env,
        private Translator $translator,
    ) {
    }

    public function execute(RegistrationFormDTO $dto): User
    {
        $config = config('johncms');

        $newUser = (new User())->create(
            [
                'name'              => $dto->name,
                'name_lat'          => $dto->nameLat,
                'password'          => md5(md5($dto->password)),
                'imname'            => $dto->imname,
                'about'             => $dto->about,
                'sex'               => $dto->sex,
                'mail'              => $dto->email,
                'rights'            => 0,
                'ip'                => $this->env->getIp(false),
                'ip_via_proxy'      => $this->env->getIpViaProxy(false),
                'browser'           => $this->env->getUserAgent(),
                'datereg'           => time(),
                'lastdate'          => time(),
                'sestime'           => time(),
                'preg'              => $config['mod_reg'] > 1 ? 1 : 0,
                'set_user'          => [],
                'set_forum'         => [],
                'set_mail'          => [],
                'smileys'           => [],
                'email_confirmed'   => ! empty($config['user_email_confirmation']) ? null : 1,
                'confirmation_code' => ! empty($config['user_email_confirmation']) ? uniqid('email_', true) : null,
            ]
        );

        if ($config['user_email_confirmation']) {
            $link = $config['homeurl'] . '/registration/confirm-email?id=' . $newUser->id . '&code=' . $newUser->confirmation_code;
            $name = ! empty($newUser->imname) ? $newUser->imname : $newUser->name;
            (new EmailMessage())->create(
                [
                    'priority' => 1,
                    'locale'   => $this->translator->getLocale(),
                    'template' => 'system::mail/templates/registration',
                    'fields'   => [
                        'email_to'        => $newUser->mail,
                        'name_to'         => $name,
                        'subject'         => __('Registration on the website'),
                        'user_name'       => $name,
                        'user_login'      => $newUser->name,
                        'link_to_confirm' => $link,
                    ],
                ]
            );
        }

        return $newUser;
    }
}
