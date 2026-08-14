<?php

/**
 * This file is part of JohnCMS Content Management System.
 *
 * @copyright JohnCMS Community
 * @license   https://opensource.org/licenses/GPL-3.0 GPL-3.0
 * @link      https://johncms.com JohnCMS Project
 */

declare(strict_types=1);

namespace Johncms\Auth;

/**
 * The secrets the auth layer hands out: password recovery links today, session cookies and API
 * tokens later. One place, so none of them can be built from something guessable.
 *
 * Generated from random_bytes() and encoded url-safe, since these values travel in links and in
 * cookie values. Stored as a SHA-256 digest and never in the clear: the copy the visitor holds
 * is the only one, so a leaked database dump does not hand out working tokens.
 *
 * SHA-256 rather than a password hash on purpose. Password hashes are deliberately slow to
 * survive being guessed, which only matters for secrets a human chose; a 32-byte random value
 * has nothing to guess, and a slow digest here would only cost a lookup on every request.
 */
final class SecureToken
{
    public const DEFAULT_BYTES = 32;

    /**
     * @param int $bytes Entropy of the secret before encoding; the string comes out longer.
     */
    public static function generate(int $bytes = self::DEFAULT_BYTES): string
    {
        // Url-safe base64 without padding: survives being pasted into a link, a cookie value or
        // an Authorization header untouched.
        return rtrim(strtr(base64_encode(random_bytes($bytes)), '+/', '-_'), '=');
    }

    public static function hash(string $token): string
    {
        return hash('sha256', $token);
    }
}
