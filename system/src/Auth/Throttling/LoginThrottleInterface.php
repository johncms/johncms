<?php

/**
 * This file is part of JohnCMS Content Management System.
 *
 * @copyright JohnCMS Community
 * @license   https://opensource.org/licenses/GPL-3.0 GPL-3.0
 * @link      https://johncms.com JohnCMS Project
 */

declare(strict_types=1);

namespace Johncms\Auth\Throttling;

/**
 * Slows down guessing at the sign-in form.
 *
 * Attempts are counted under a key rather than on the account, because a password guesser and
 * the account's owner are not the same person: locking the account would let anyone lock anyone
 * out by typing a wrong password often enough. Two keys are counted at once — the login being
 * tried and the address trying it — so neither rotating addresses nor walking through logins
 * gets around it.
 */
interface LoginThrottleInterface
{
    /**
     * How long the key has to wait before another attempt is looked at. Zero means it may go
     * ahead now.
     */
    public function retryAfter(string $key): int;

    /**
     * Whether enough has gone wrong under this key that the form should ask for a verification
     * code before the password is checked at all.
     */
    public function requiresVerification(string $key): bool;

    public function registerFailure(string $key): void;

    /**
     * Forgets the failures under the key. Called when the credentials turn out to be right.
     */
    public function clear(string $key): void;
}
