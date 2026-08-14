<?php

/**
 * This file is part of JohnCMS Content Management System.
 *
 * @copyright JohnCMS Community
 * @license   https://opensource.org/licenses/GPL-3.0 GPL-3.0
 * @link      https://johncms.com JohnCMS Project
 */

declare(strict_types=1);

namespace Johncms\Auth\Session;

use Johncms\Auth\Password\PasswordResetTokenRepositoryInterface;

/**
 * Removes what the auth tables no longer need.
 *
 * They grow with every sign-in and every forgotten password and nothing ever shrinks them on its
 * own, which is how `cms_users_iphistory` became what it is. A session is kept for a while after
 * it stops working, so the profile can still show that a device was signed out rather than
 * silently losing the row.
 */
final readonly class ExpiredAuthDataCleaner
{
    /** How long a dead session is kept before it is dropped, in seconds. */
    public const RETENTION = 2592000;

    public function __construct(
        private AuthSessionRepositoryInterface $sessions,
        private PasswordResetTokenRepositoryInterface $resetTokens,
    ) {
    }

    /**
     * @return array{sessions: int, reset_tokens: int} What was removed.
     */
    public function clean(?int $now = null, int $retention = self::RETENTION): array
    {
        $now ??= time();

        return [
            'sessions' => $this->sessions->deleteDeadBefore($now - $retention),
            // Recovery links are worthless the moment they expire and nothing displays them,
            // so they go as soon as they are dead.
            'reset_tokens' => $this->resetTokens->deleteExpiredBefore($now),
        ];
    }
}
