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
 * The one way passwords are turned into what the database keeps, and the one way they are
 * checked against it.
 *
 * An interface rather than a pair of functions because the algorithm is a moving target: what
 * is strong enough today is not in five years, and the point of needsRehash() is that the
 * change costs nothing to the people already signed up.
 */
interface PasswordHasherInterface
{
    public function hash(string $password): string;

    public function verify(string $password, string $storedHash): bool;

    /**
     * Whether the stored hash was made by something weaker than what is configured now — an
     * older algorithm, weaker parameters, or the md5 scheme this project used to use.
     *
     * The password itself is needed to redo it, so the only moment this can be acted on is a
     * successful sign-in. Nothing else in the application ever sees a password in the clear.
     */
    public function needsRehash(string $storedHash): bool;
}
