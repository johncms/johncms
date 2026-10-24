<?php

declare(strict_types=1);

namespace Johncms\Modules\Admin\Application\UseCases;

use Johncms\Auth\Authorization\Role;
use Johncms\Auth\Authorization\RoleRepositoryInterface;
use Johncms\Modules\Admin\Application\DTO\UserListPageDTO;
use Johncms\Modules\Admin\Application\DTO\UserListStatsDTO;
use Johncms\Modules\Admin\Domain\Enums\UserListActivity;
use Johncms\Modules\Admin\Domain\Enums\UserListRegistered;
use Johncms\Modules\Admin\Domain\Enums\UserListStatus;
use Johncms\Modules\Admin\Domain\Repository\UserListRepositoryInterface;
use Johncms\Modules\Admin\Domain\ValueObjects\UserListFilter;

/**
 * The accounts of the site as the panel lists them: narrowed by a filter, a page at a time.
 */
final readonly class GetUserListUseCase
{
    public function __construct(
        private UserListRepositoryInterface $users,
        private RoleRepositoryInterface $roles,
    ) {
    }

    public function count(UserListFilter $filter, ?int $now = null): int
    {
        return $this->users->count($filter, $now ?? time());
    }

    public function getPage(UserListFilter $filter, int $limit, int $offset, ?int $now = null): UserListPageDTO
    {
        $now ??= time();
        $users = $this->users->getPage($filter, $now, $limit, $offset);
        $ids = $users->modelKeys();

        return new UserListPageDTO(
            $users,
            $ids === [] ? [] : $this->roles->grantedRolesFor($ids, $now),
            $this->users->getBanEnds($ids, $now),
        );
    }

    /**
     * Each number is the size of the list one of the filters gives, so a number and the list it
     * opens never disagree.
     */
    public function stats(?int $now = null): UserListStatsDTO
    {
        $now ??= time();

        return new UserListStatsDTO(
            total: $this->users->count(new UserListFilter(), $now),
            online: $this->users->count(new UserListFilter(activity: UserListActivity::ONLINE), $now),
            registeredToday: $this->users->count(new UserListFilter(registered: UserListRegistered::DAY), $now),
            banned: $this->users->count(new UserListFilter(status: UserListStatus::BANNED), $now),
            pending: $this->users->count(new UserListFilter(status: UserListStatus::PENDING), $now),
        );
    }

    /**
     * The roles the list can be narrowed to: those handed to accounts. The default roles are held
     * by everybody and the guest role by nobody signed in, so neither tells anyone apart.
     *
     * @return list<Role>
     */
    public function grantableRoles(): array
    {
        return array_values(
            $this->roles->all()
                ->filter(static fn (Role $role): bool => ! $role->is_default && ! $role->is_guest)
                ->all()
        );
    }
}
