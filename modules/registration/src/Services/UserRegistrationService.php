<?php

/**
 * This file is part of JohnCMS Content Management System.
 *
 * @copyright JohnCMS Community
 * @license   https://opensource.org/licenses/GPL-3.0 GPL-3.0
 * @link      https://johncms.com JohnCMS Project
 */

declare(strict_types=1);

namespace Registration\Services;

use Johncms\i18n\Translator;
use Johncms\Mail\EmailMessage;
use Johncms\Users\AuthProviders\SessionAuthProvider;
use Johncms\Users\Exceptions\RuntimeException;
use Johncms\Users\User;
use Johncms\Users\UserManager;

class UserRegistrationService
{
    protected bool $emailConfirmation = false;
    protected bool $moderation = false;

    public function __construct()
    {
        $this->moderation = config('registration.moderation', false);
        $this->emailConfirmation = config('registration.email_confirmation', false);
    }

    public function moderation(): bool
    {
        return $this->moderation;
    }

    public function emailConfirmation(): bool
    {
        return $this->emailConfirmation;
    }

    public function registerUser(array $fields): User
    {
        $userManager = di(UserManager::class);

        $fields['confirmed'] = (! $this->moderation);
        $fields['email_confirmed'] = (! $this->emailConfirmation);

        if ($this->emailConfirmation) {
            $fields['confirmation_code'] = uniqid('email_', true);
        }

        // Create user
        $user = $userManager->create($fields);

        if ($this->emailConfirmation) {
            $this->sendConfirmationEmail($user);
        }

        // Authorize the user
        if ($fields['confirmed'] && $fields['email_confirmed']) {
            $sessionProvider = di(SessionAuthProvider::class);
            $sessionProvider->store($user);
        }

        return $user;
    }

    protected function sendConfirmationEmail(User $user)
    {
        $translator = di(Translator::class);
        $config = di('config')['johncms'];

        $confirmUrl = route('registration.confirmEmail');
        $link = $config['home_url'] . $confirmUrl . '?id=' . $user->id . '&code=' . $user->confirmation_code;
        $name = $user->login;
        (new EmailMessage())->create(
            [
                'priority' => 1,
                'locale'   => $translator->getLocale(),
                'template' => 'system::mail/templates/registration',
                'fields'   => [
                    'email_to'        => $user->email,
                    'name_to'         => $name,
                    'subject'         => __('Registration on the website'),
                    'user_name'       => $name,
                    'user_login'      => $user->login,
                    'link_to_confirm' => $link,
                ],
            ]
        );
    }

    public function confirmEmail(User $confirmUser, string $code): void
    {
        if (! $confirmUser->email_confirmed && $confirmUser->confirmation_code === $code) {
            $confirmUser->email_confirmed = true;
            $confirmUser->confirmation_code = null;
            $confirmUser->save();
        } else {
            throw new RuntimeException(__('The user was not found, has already been confirmed, or the confirmation code is incorrect'));
        }
    }
}
