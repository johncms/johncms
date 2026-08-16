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

use Illuminate\Support\Str;
use Johncms\Auth\Events\AuthEventLoggerInterface;
use Johncms\Security\ClientInfoDTO;
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
 */
final readonly class RegisterViaExternalProviderUseCase
{
    public function __construct(
        private AuthenticateViaExternalProviderUseCase $identities,
        private AuthEventLoggerInterface $eventLogger,
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

        $user = new User();
        $user->fill(
            [
                'name'            => $name,
                'name_lat'        => Str::slug($name, '_'),
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
                'smileys'         => [],
                // The address is confirmed when the provider vouches for it: asking somebody to
                // confirm an address their provider just proved they own is theatre.
                'email_confirmed' => $identity->emailVerified ? 1 : null,
            ]
        );
        $user->save();

        $this->identities->link($user->id, $provider, $identity, $now);
        $this->eventLogger->log('oauth.register', $user->id, ['provider' => $provider]);

        return $user;
    }
}
