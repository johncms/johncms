<?php

/**
 * This file is part of JohnCMS Content Management System.
 *
 * @copyright JohnCMS Community
 * @license   https://opensource.org/licenses/GPL-3.0 GPL-3.0
 * @link      https://johncms.com JohnCMS Project
 */

declare(strict_types=1);

namespace Johncms\Auth\Password;

/**
 * Recognises and checks the scheme this project stored passwords in before: md5(md5($password)).
 *
 * It exists so that upgrading a site does not force everyone to reset their password. Nobody
 * keeps this scheme for long: the first successful sign-in replaces the stored value with a
 * modern hash, and this class is what tells the hasher that the replacement is due.
 *
 * Kept apart from the hasher rather than folded into it, so that the day the last of these
 * disappears from the database, removing the support is deleting one class and one call.
 */
final class LegacyMd5PasswordVerifier
{
    /**
     * Whether the stored value looks like the old scheme rather than a password_hash() result.
     * The two are unmistakable: password_hash() always produces a value starting with '$'.
     */
    public function supports(string $storedHash): bool
    {
        return preg_match('/^[a-f0-9]{32}$/i', $storedHash) === 1;
    }

    public function verify(string $password, string $storedHash): bool
    {
        // Constant-time even here: the comparison is against a value derived from a secret, and
        // a fast-exit comparison is a habit that costs nothing to avoid.
        return hash_equals($storedHash, md5(md5($password)));
    }
}
