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
use Johncms\System\Users\User as LegacyUser;
use Johncms\System\Users\UserFactory as LegacyUserFactory;

/**
 * Loads the visitor of the request being served into the two shared current-user services.
 *
 * Both of them are singletons that dozens of controllers and services take in their constructor,
 * so they cannot be rebuilt per request — instead their state is replaced here, once per request.
 *
 * Who the visitor is has already been decided by the authenticator chain behind CurrentUser;
 * this only fills the models the older code reads. That separation is the point: identifying a
 * visitor happens once, in one place, whatever the credentials were, and everything here is
 * just a lookup by id.
 */
final class CurrentUserAuthenticator
{
    /**
     * The user the shared instances currently hold. Resolving the identity is cheap after the
     * first time, but the two lookups below are not, and the kernel may call this again for the
     * same visitor.
     */
    private ?int $loadedUserId = null;

    public function __construct(
        private readonly CurrentUser $currentUser,
        private readonly LegacyUserFactory $legacyUserFactory,
        private readonly LegacyUser $legacyUser,
        private readonly UserFactory $userFactory,
        private readonly User $user,
    ) {
    }

    public function authenticate(): void
    {
        $userId = $this->currentUser->identity()->userId;

        if ($this->loadedUserId === $userId) {
            return;
        }

        $this->loadedUserId = $userId;

        // The legacy user goes first: it is the one that records the IP history, and the
        // Eloquent user then finds the address already up to date and writes nothing.
        $this->legacyUserFactory->load($this->legacyUser, $userId);
        $this->userFactory->load($this->user, $userId);
    }

    /**
     * Drops the cached user id so the next call reloads. Called when the visitor changes inside
     * one request — signing in, and stepping into or out of impersonation.
     */
    public function forget(): void
    {
        $this->loadedUserId = null;
    }
}
