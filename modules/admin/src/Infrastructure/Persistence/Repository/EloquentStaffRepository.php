<?php

declare(strict_types=1);

namespace Johncms\Modules\Admin\Infrastructure\Persistence\Repository;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Johncms\Auth\Authorization\UserRole;
use Johncms\Modules\Admin\Domain\Repository\StaffRepositoryInterface;
use Johncms\Users\User;

final class EloquentStaffRepository implements StaffRepositoryInterface
{
    public function getStaff(int $now): Collection
    {
        /** @var Collection<int, User> $staff Static analysis loses the model type through whereIn(). */
        $staff = User::query()
            ->whereIn('id', $this->grantsInForce($now)->select('user_id'))
            ->orderBy('name')
            ->get();

        return $staff;
    }

    public function getRoleAssignments(int $now): Collection
    {
        /** @var Collection<int, UserRole> $assignments */
        $assignments = $this->grantsInForce($now)->get();

        return $assignments;
    }

    public function countStaff(int $now): int
    {
        return $this->grantsInForce($now)->distinct()->count('user_id');
    }

    /**
     * The grants that have not run out. A row with an expiry in the past is dead: temporary
     * moderation stops counting on its own, without anybody clearing it.
     *
     * @return Builder<UserRole>
     */
    private function grantsInForce(int $now): Builder
    {
        return UserRole::query()->where(
            static function (Builder $query) use ($now): void {
                $query->whereNull('expires_at')->orWhere('expires_at', '>', $now);
            }
        );
    }
}
