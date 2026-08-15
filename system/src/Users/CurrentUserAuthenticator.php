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
use Johncms\Users\User as LegacyUser;
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
     * The user the shared instance currently holds. Resolving the identity is cheap after the
     * first time, but the lookup below is not, and the kernel may call this again for the same
     * visitor.
     */
    private ?int $loadedUserId = null;

    public function __construct(
        private readonly CurrentUser $currentUser,
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
