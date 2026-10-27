<?php

/**
 * This file is part of JohnCMS Content Management System.
 *
 * @copyright JohnCMS Community
 * @license   https://opensource.org/licenses/GPL-3.0 GPL-3.0
 * @link      https://johncms.com JohnCMS Project
 */

declare(strict_types=1);

namespace Johncms\Auth\External;

use Johncms\Auth\Events\AuthEventLoggerInterface;
use Johncms\Auth\RegistrationSettings;
use Johncms\Auth\SecureToken;
use Johncms\Mail\Queue\MailQueueInterface;
use Johncms\Mail\Queue\QueuedEmailDTO;
use Johncms\Security\ClientInfoDTO;
use Johncms\System\i18n\Translator;
use Johncms\Users\LoginName;
use Johncms\Users\User;

/**
 * Creates the account behind an identity, once the visitor has filled in what the provider could
 * not give.
 *
 * In the core rather than in the registration module for the reason the sign-in use case is
 * there: the panel's login screen must offer the provider buttons on a site where the public
 * registration module is switched off, and `admin` may not depend on it.
 *
 * The account has no password. That is not an empty password: the hasher refuses an empty stored
 * hash outright, so "signed up through a service" can never be walked into by sending a blank
 * password field.
 *
 * The address is confirmed only when the provider vouches for that very address. One the visitor
 * typed in is confirmed by mail, as on the registration form: taking it on trust would hand a
 * stranger's address to password recovery and to the matching of accounts by address.
 */
final readonly class RegisterViaExternalProviderUseCase
{
    public function __construct(
        private AuthenticateViaExternalProviderUseCase $identities,
        private AuthEventLoggerInterface $eventLogger,
        private RegistrationSettings $settings,
        private MailQueueInterface $mailQueue,
        private Translator $translator,
    ) {
    }

    public function execute(
        string $provider,
        ExternalIdentityDTO $identity,
        string $name,
        string $email,
        ClientInfoDTO $client,
        bool $moderationEnabled = false,
        ?int $now = null,
    ): User {
        $now ??= time();
        $confirmed = ! $this->settings->emailConfirmationRequired() || self::vouchedFor($identity, $email);

        $user = new User();
        $user->fill(
            [
                'name'            => $name,
                'name_lat'        => LoginName::of($name),
                'imname'          => $identity->nickname ?? '',
                'mail'            => $email,
                'ip'              => $client->ip,
                'ip_via_proxy'    => $client->ipViaProxy,
                'browser'         => $client->userAgent,
                'datereg'         => $now,
                'lastdate'        => $now,
                'sestime'         => $now,
                'preg'            => $moderationEnabled ? 0 : 1,
                'set_user'        => [],
                'set_forum'       => [],
                'set_mail'        => [],
                'smileys'           => [],
                'email_confirmed'   => $confirmed ? 1 : null,
                'confirmation_code' => $confirmed ? null : SecureToken::generate(),
            ]
        );
        $user->save();

        if (! $confirmed) {
            $this->sendConfirmation($user);
        }

        $this->identities->link($user->id, $provider, $identity, $now);
        $this->eventLogger->log('oauth.register', $user->id, ['provider' => $provider]);

        return $user;
    }

    /**
     * Asking somebody to confirm an address their provider just proved they own is theatre — but
     * the proof covers the address the provider sent, not whatever came in with the form.
     */
    private static function vouchedFor(ExternalIdentityDTO $identity, string $email): bool
    {
        return $identity->emailVerified
            && $identity->email !== null
            && mb_strtolower($identity->email) === mb_strtolower($email);
    }

    /**
     * The letter of the registration form, with its link. The page behind the link belongs to the
     * registration module, which is there whenever this runs: the permission that lets a guest
     * sign up is the one it declares.
     */
    private function sendConfirmation(User $user): void
    {
        $name = $user->imname !== '' ? $user->imname : $user->name;

        $this->mailQueue->push(
            new QueuedEmailDTO(
                template: '@theme/emails/registration.twig',
                locale: $this->translator->getLocale(),
                recipient: $user->mail,
                recipientName: $name,
                subject: __('Registration on the website'),
                priority: 1,
                variables: [
                    'user_name'       => $name,
                    'user_login'      => $user->name,
                    'link_to_confirm' => config('johncms.homeurl') . '/registration/confirm-email?id=' . $user->id
                        . '&code=' . $user->confirmation_code,
                ],
            )
        );
    }
}
