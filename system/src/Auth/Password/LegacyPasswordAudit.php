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

use Johncms\Users\User;

/**
 * How much of the old password scheme is left in the database.
 *
 * Accounts move off it as their owners sign in, which means the tail is made of people who have
 * not come back. Somebody has to be able to see how long that tail is — otherwise the support
 * for the old scheme gets removed one day on the assumption that it is empty.
 *
 * Lives beside the verifier that recognises a single value, because the two share the same
 * knowledge and are meant to be deleted together.
 */
final class LegacyPasswordAudit
{
    /**
     * The old scheme produced a 32-character hex digest; password_hash() never produces anything
     * of that length. Comparing the length rather than matching a pattern keeps this working on
     * every database the CMS runs on.
     */
    public function countAccountsOnLegacyHash(): int
    {
        return User::query()->whereRaw('LENGTH(`password`) = 32')->count();
    }

    public function countAccounts(): int
    {
        return User::query()->count();
    }
}
