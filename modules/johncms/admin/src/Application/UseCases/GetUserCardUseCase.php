<?php

declare(strict_types=1);

namespace Johncms\Modules\Admin\Application\UseCases;

use Johncms\Auth\Authorization\RoleRepositoryInterface;
use Johncms\Modules\Admin\Application\DTO\UserCardDTO;
use Johncms\Modules\Admin\Application\Exceptions\UserNotFoundException;
use Johncms\Modules\Admin\Domain\Repository\UserCardRepositoryInterface;

/**
 * The page of one account in the panel: who it is, where it came from, what it did and what it is
 * under.
 */
final readonly class GetUserCardUseCase
{
    /** How many earlier addresses the page lists; the search by address has the rest. */
    private const RECENT_IPS = 5;

    public function __construct(
        private UserCardRepositoryInterface $users,
        private RoleRepositoryInterface $roles,
    ) {
    }

    public function execute(int $id, ?int $now = null): UserCardDTO
    {
        $now ??= time();
        $user = $this->users->findById($id) ?? throw new UserNotFoundException();

        return new UserCardDTO(
            user: $user,
            roles: array_values($this->roles->grantedTo($user->id, $now)->all()),
            activeBans: $this->users->getActiveBans($user->id, $now),
            banCount: $this->users->countBans($user->id),
            recentIps: $this->users->getRecentIps($user->id, self::RECENT_IPS),
            ipCount: $this->users->countIps($user->id),
        );
    }
}
