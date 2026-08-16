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

/**
 * Who the provider says came back: the whole of what a provider hands to the core.
 *
 * Everything else about the flow — the state, the PKCE verifier, matching this against an
 * account, opening a session — stays in the core. A provider is an adapter, and the most a
 * badly written one can spoil is its own button.
 */
final readonly class ExternalIdentityDTO
{
    /**
     * @param string      $providerUserId The identifier at the provider. Never an email: people
     *                                    change those, and reusing one as the key would hand the
     *                                    account to whoever gets the address next.
     * @param bool        $emailVerified  Whether the provider vouches for the address. Linking to
     *                                    an existing account by email is only allowed when it does.
     */
    public function __construct(
        public string $providerUserId,
        public ?string $email = null,
        public bool $emailVerified = false,
        public ?string $nickname = null,
        public ?string $avatarUrl = null,
    ) {
    }
}
