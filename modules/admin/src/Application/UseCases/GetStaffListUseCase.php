<?php

declare(strict_types=1);

namespace Johncms\Modules\Admin\Application\UseCases;

use Illuminate\Support\Collection;
use Johncms\Auth\Authorization\Role;
use Johncms\Auth\Authorization\RoleRepositoryInterface;
use Johncms\Auth\Authorization\UserRole;
use Johncms\Modules\Admin\Application\DTO\StaffGroupDTO;
use Johncms\Modules\Admin\Application\DTO\StaffListDTO;
use Johncms\Modules\Admin\Application\Services\AdminUserRowMapper;
use Johncms\Modules\Admin\Domain\Repository\StaffRepositoryInterface;
use Johncms\Users\User;

/**
 * The staff of the site, grouped by the roles they were given.
 *
 * Somebody holding two roles appears under both — which is the point of roles, and something the
 * single number they replaced could not express. The total counts people, not memberships.
 */
final readonly class GetStaffListUseCase
{
    public function __construct(
        private StaffRepositoryInterface $staffRepository,
        private RoleRepositoryInterface $roles,
        private AdminUserRowMapper $rowMapper,
    ) {
    }

    public function execute(?int $now = null): StaffListDTO
    {
        $now ??= time();

        $staff = $this->staffRepository->getStaff($now)->keyBy('id');
        $membersByRole = $this->groupByRole($this->staffRepository->getRoleAssignments($now), $staff);

        $groups = [];

        /** @var Role $role */
        foreach ($this->roles->all() as $role) {
            $members = $membersByRole[$role->id] ?? [];

            if ($members === []) {
                continue;
            }

            $groups[] = new StaffGroupDTO($role->display_name, $this->rowMapper->mapMany(new Collection($members)));
        }

        return new StaffListDTO($groups, $staff->count());
    }

    /**
     * @param Collection<int, UserRole> $assignments
     * @param Collection<int, User>     $staff       Keyed by account id.
     * @return array<int, list<User>>
     */
    private function groupByRole(Collection $assignments, Collection $staff): array
    {
        $grouped = [];

        foreach ($assignments as $assignment) {
            $user = $staff->get($assignment->user_id);

            if ($user instanceof User) {
                $grouped[$assignment->role_id][] = $user;
            }
        }

        return $grouped;
    }
}
