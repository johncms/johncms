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
 * Hashes passwords with PHP's own password_hash(), which is bcrypt by default and argon2id
 * where a site configures it.
 *
 * The scheme it replaces was md5(md5($password)): unsalted, and fast enough that a leaked table
 * of them is a list of passwords rather than a list of hashes. What password_hash() gives
 * instead is a per-password salt and a cost that can be raised as hardware gets faster.
 */
final readonly class NativePasswordHasher implements PasswordHasherInterface
{
    /**
     * @param string|int         $algorithm A password_hash() algorithm constant.
     * @param array<string, int> $options   Passed to password_hash() as-is; empty means the
     *                                      defaults of the algorithm.
     */
    public function __construct(
        private LegacyMd5PasswordVerifier $legacy,
        private string|int|null $algorithm = PASSWORD_DEFAULT,
        private array $options = [],
    ) {
    }

    public function hash(string $password): string
    {
        return password_hash($password, $this->algorithm, $this->options);
    }

    public function verify(string $password, string $storedHash): bool
    {
        if ($storedHash === '') {
            // No password on the account: nothing can match it. Answering false rather than
            // letting password_verify() decide keeps an empty column from ever being a way in.
            return false;
        }

        if ($this->legacy->supports($storedHash)) {
            return $this->legacy->verify($password, $storedHash);
        }

        return password_verify($password, $storedHash);
    }

    public function needsRehash(string $storedHash): bool
    {
        if ($this->legacy->supports($storedHash)) {
            return true;
        }

        return password_needs_rehash($storedHash, $this->algorithm, $this->options);
    }
}
