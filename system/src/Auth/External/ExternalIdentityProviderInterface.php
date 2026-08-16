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
 * A service people can sign in with.
 *
 * Deliberately not an OAuth2 contract. Describing the authorization code flow here would mean a
 * module for Telegram (a signed payload, no code at all) or Steam (OpenID 2.0) could not
 * implement it — so the contract asks two questions instead: where do we send the visitor, and
 * what do we make of them coming back. AbstractOAuth2Provider covers the usual case in ~30 lines
 * per provider; anything exotic implements this directly.
 *
 * A module registers its own provider with the `johncms.auth.external_provider` tag. It needs no
 * routes: the callback address is a core route parameterised by the provider key, so the author
 * of a module thinks about their service and about nothing else — not about state, not about
 * PKCE, not about how accounts are matched.
 */
interface ExternalIdentityProviderInterface
{
    /**
     * The key this provider is known by: in the URLs, in the config and in `user_identities`.
     * Never renamed — stored rows refer to it.
     */
    public function key(): string;

    /** The name on the button, translated. */
    public function label(): string;

    /** Icon id of the sprite, or an empty string when the provider ships none. */
    public function icon(): string;

    /**
     * Whether the site has given this provider the keys it needs. An unconfigured provider is not
     * offered: a button leading to somebody else's error page helps nobody.
     */
    public function isConfigured(): bool;

    /**
     * Where to send the visitor to authorize us.
     */
    public function startUrl(ExternalAuthContextDTO $context): string;

    /**
     * Who came back, as the provider knows them.
     *
     * @throws ExternalAuthException When the provider refused, answered with nonsense or could
     *                               not be reached.
     */
    public function handleCallback(ExternalCallbackDTO $callback, ExternalAuthContextDTO $context): ExternalIdentityDTO;
}
