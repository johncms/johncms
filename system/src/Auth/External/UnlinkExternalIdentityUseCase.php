<?php

/**
 * This file is part of JohnCMS Content Management System.
 *
 * @copyright JohnCMS Community
 * @license   https://opensource.org/licenses/GPL-3.0 GPL-3.0
 * @link      https://johncms.com JohnCMS Project
 */

declare(strict_types=1);

namespace Johncms\Auth\External;

use Johncms\Auth\Events\AuthEventLoggerInterface;
use Johncms\Users\Repository\UserRepositoryInterface;

/**
 * Detaches an external service from an account.
 *
 * The one rule worth stating: an account may never be left without a way in. Somebody who signed
 * up through a service has no password, and unlinking their only service would lock them out of
 * their own account with no way to recover it — password recovery needs a password to reset.
 */
final readonly class UnlinkExternalIdentityUseCase
{
    public function __construct(
        private UserIdentityRepositoryInterface $identities,
        private UserRepositoryInterface $users,
        private AuthEventLoggerInterface $eventLogger,
    ) {
    }

    /**
     * @throws ExternalAuthException When this is the only way the account can be signed into.
     */
    public function execute(int $userId, string $provider): void
    {
        $link = $this->identities->findForUser($userId, $provider);

        if ($link === null) {
            return;
        }

        if (! $this->hasAnotherWayIn($userId, $provider)) {
            throw new ExternalAuthException(
                __('This is the only way to sign in to the account. Set a password first.')
            );
        }

        $this->identities->delete($link->id);
        $this->eventLogger->log('oauth.unlinked', $userId, ['provider' => $provider]);
    }

    private function hasAnotherWayIn(int $userId, string $provider): bool
    {
        $user = $this->users->find($userId);

        // Cast rather than a strict comparison: "no password" reaches this as an empty string on
        // an account that never had one and as null on a model that did not load the column.
        if ($user !== null && (string) $user->password !== '') {
            return true;
        }

        return $this->identities->allForUser($userId)
            ->contains(static fn (UserIdentity $identity): bool => $identity->provider !== $provider);
    }
}
