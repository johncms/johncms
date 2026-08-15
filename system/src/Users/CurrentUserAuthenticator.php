<?php

/**
 * This file is part of JohnCMS Content Management System.
 *
 * @copyright JohnCMS Community
 * @license   https://opensource.org/licenses/GPL-3.0 GPL-3.0
 * @link      https://johncms.com JohnCMS Project
 */

declare(strict_types=1);

namespace Johncms\Users;

use Johncms\Auth\CurrentUser;

/**
 * Mirrors the visitor of the request into the shared User instance the older code injects.
 *
 * That instance is a singleton dozens of controllers and services take in their constructor, so
 * it cannot be rebuilt per request — its state is replaced here instead, once per request. The
 * mirror goes away with the last constructor asking for the model instead of CurrentUser.
 */
final class CurrentUserAuthenticator
{
    /**
     * The user the shared instance currently holds, so a second call for the same visitor is
     * free.
     */
    private ?int $loadedUserId = null;

    public function __construct(
        private readonly CurrentUser $currentUser,
        private readonly User $user,
    ) {
    }

    public function authenticate(): void
    {
        $userId = $this->currentUser->id();

        if ($this->loadedUserId === $userId) {
            return;
        }

        $this->loadedUserId = $userId;

        $visitor = $this->currentUser->user();

        $this->user->setRawAttributes($visitor->getAttributes(), true);
        $this->user->exists = $visitor->exists;
        // Anything loaded for the previous visitor (the ip history, the notifications) belongs to
        // them, and Eloquent would keep serving it from here as if it were the current user's.
        $this->user->setRelations([]);
    }
}
