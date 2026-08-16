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

use Johncms\Auth\Authorization\AccessCheckerInterface;
use Johncms\Auth\Authorization\PermissionResolver;
use Johncms\Auth\Events\AuthEventLoggerInterface;
use Johncms\Auth\Identity;
use Johncms\Users\User;

/**
 * What an identity from a provider means for this site: a sign-in, a new account, or a refusal.
 *
 * Does not open the session — that belongs to the caller, which is the only one that knows where
 * the visitor is going next. The same split the password sign-in uses.
 */
final readonly class AuthenticateViaExternalProviderUseCase
{
    /**
     * Declared by the registration module. Named as a string because the core cannot depend on a
     * module that a site may have removed — and if it is gone, so is registration.
     */
    private const REGISTER_PERMISSION = 'registration.register';

    public function __construct(
        private UserIdentityRepositoryInterface $identities,
        private AccessCheckerInterface $accessChecker,
        private PermissionResolver $permissions,
        private AuthEventLoggerInterface $eventLogger,
    ) {
    }

    public function execute(string $provider, ExternalIdentityDTO $identity, ?int $now = null): ExternalAuthResultDTO
    {
        $now ??= time();
        $linked = $this->identities->findByProviderUser($provider, $identity->providerUserId);

        if ($linked !== null) {
            $this->identities->touchLogin($linked->id, $now);
            $this->eventLogger->log('oauth.login', $linked->user_id, ['provider' => $provider]);

            return new ExternalAuthResultDTO(ExternalAuthStatus::SignedIn, $linked->user_id);
        }

        $existing = $this->findByEmail($identity->email);

        if ($existing !== null) {
            // Matching by address is how these features turn into account takeovers: an attacker
            // registers somewhere with a victim's address and walks in. It is allowed only when
            // both sides vouch for the address — the provider says it verified it, and the
            // account here confirmed it too.
            if (! $identity->emailVerified || ! $existing->email_confirmed) {
                throw new ExternalAccountConflictException(
                    __('This email address is already registered. Sign in with your password to link the account.')
                );
            }

            $this->link($existing->id, $provider, $identity, $now);
            $this->eventLogger->log('oauth.login', $existing->id, ['provider' => $provider]);

            return new ExternalAuthResultDTO(ExternalAuthStatus::SignedIn, $existing->id);
        }

        // Nobody to sign in, so what happens next is a registration — and the site may not allow
        // one. The question is asked of the guest role, which is who is standing here.
        //
        // Resolved rather than asked of a bare Identity::guest(): the identity of a guest carries
        // no roles until the resolver fills them in, so an unresolved one is refused everything —
        // which reads as "registration is closed" on a site where it is open.
        if (! $this->accessChecker->allowsFor($this->permissions->resolve(Identity::guest()), self::REGISTER_PERMISSION)) {
            return new ExternalAuthResultDTO(ExternalAuthStatus::RegistrationClosed);
        }

        return new ExternalAuthResultDTO(ExternalAuthStatus::NeedsProfile);
    }

    /**
     * Records the link and returns it. Used both by the automatic match above and by a visitor
     * linking a service to the account they are signed into.
     */
    public function link(int $userId, string $provider, ExternalIdentityDTO $identity, ?int $now = null): UserIdentity
    {
        $now ??= time();

        $link = $this->identities->create(
            [
                'user_id'  => $userId,
                'provider' => $provider,
                // Cut to what the columns hold. These values come from somebody else's API and
                // are a snapshot for reference, so a long one must not be able to fail a sign-in
                // with a database error — which is exactly what VK did with its avatar URLs.
                'provider_user_id' => mb_substr($identity->providerUserId, 0, 191),
                'email'            => self::clip($identity->email, 191),
                'nickname'         => self::clip($identity->nickname, 191),
                'avatar_url'       => $identity->avatarUrl,
                'linked_at'        => $now,
                'last_login_at'    => $now,
            ]
        );

        $this->eventLogger->log('oauth.linked', $userId, ['provider' => $provider]);

        return $link;
    }

    private static function clip(?string $value, int $length): ?string
    {
        return $value === null ? null : mb_substr($value, 0, $length);
    }

    private function findByEmail(?string $email): ?User
    {
        if ($email === null || $email === '') {
            return null;
        }

        return User::query()->where('mail', '=', $email)->first();
    }
}
