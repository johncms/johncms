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

use Johncms\Auth\SecureToken;
use Johncms\Http\Session;

/**
 * The one-time values that make the round trip to a provider safe, kept in the PHP session.
 *
 * `state` is what ties the answer to the request that started it. Without it the callback can be
 * replayed by anybody: an attacker starts a flow with their own account at the provider, sends
 * the resulting callback URL to a signed-in victim, and their account gets linked to the
 * victim's — after which they sign in as the victim whenever they like.
 *
 * PKCE covers the second half: the code that comes back is worthless without the verifier that
 * never left this session, so intercepting the callback URL is not enough to exchange it.
 *
 * Single-use and short-lived, both on purpose: a state that survives its first use is not a
 * state, and one that lives forever is a replay waiting for an opportunity.
 */
final readonly class OAuthStateStorage
{
    private const KEY = 'oauth_flow';

    /** How long a started flow may take, in seconds. Signing in at the provider is a minute's work. */
    public const TTL = 600;

    public function __construct(private Session $session)
    {
    }

    /**
     * Starts a flow and returns the context the provider needs.
     *
     * @param string $intent Whether this ends in a sign-in or in a link to the account already
     *                       signed in — decided when the flow starts, never by the callback.
     */
    public function start(string $provider, string $redirectUri, string $intent, ?int $userId = null): ExternalAuthContextDTO
    {
        $state = SecureToken::generate();
        $verifier = SecureToken::generate();

        $this->session->set(
            self::KEY,
            [
                'provider'      => $provider,
                'state'         => $state,
                'code_verifier' => $verifier,
                // Remembered rather than rebuilt when the visitor comes back. Providers compare
                // the redirect_uri of the token request against the one the authorize request
                // carried, byte for byte, and the two are computed from different requests —
                // a scheme decided by a proxy header or a host with a port is enough to make
                // them differ, which the provider then reports as a security error.
                'redirect_uri'  => $redirectUri,
                'intent'        => $intent,
                'user_id'       => $userId,
                'created_at'    => time(),
            ]
        );

        return new ExternalAuthContextDTO(
            redirectUri: $redirectUri,
            state: $state,
            codeVerifier: $verifier,
            codeChallenge: self::challengeOf($verifier),
        );
    }

    /**
     * Consumes the flow this callback belongs to.
     *
     * @return array{context: ExternalAuthContextDTO, intent: string, user_id: int|null}
     *
     * @throws ExternalAuthException When there is no flow, it belongs to another provider, the
     *                               state does not match or it has expired.
     */
    public function consume(string $provider, string $state): array
    {
        /** @var array<string, mixed>|null $flow */
        $flow = $this->session->get(self::KEY);
        // Spent whatever happens: a state that survives a failed attempt can be tried again.
        $this->session->remove(self::KEY);

        if (! is_array($flow) || $flow['provider'] !== $provider) {
            throw new ExternalAuthException(__('The sign-in attempt has expired, please try again'));
        }

        if ($state === '' || ! hash_equals((string) $flow['state'], $state)) {
            throw new ExternalAuthException(__('The sign-in attempt could not be verified, please try again'));
        }

        if (time() - (int) $flow['created_at'] > self::TTL) {
            throw new ExternalAuthException(__('The sign-in attempt has expired, please try again'));
        }

        $verifier = (string) $flow['code_verifier'];

        return [
            'context' => new ExternalAuthContextDTO(
                redirectUri: (string) $flow['redirect_uri'],
                state: $state,
                codeVerifier: $verifier,
                codeChallenge: self::challengeOf($verifier),
            ),
            'intent'  => (string) $flow['intent'],
            'user_id' => $flow['user_id'] === null ? null : (int) $flow['user_id'],
        ];
    }

    /**
     * The S256 challenge: base64url of the SHA-256 of the verifier, as RFC 7636 spells it.
     */
    private static function challengeOf(string $verifier): string
    {
        return rtrim(strtr(base64_encode(hash('sha256', $verifier, true)), '+/', '-_'), '=');
    }
}
