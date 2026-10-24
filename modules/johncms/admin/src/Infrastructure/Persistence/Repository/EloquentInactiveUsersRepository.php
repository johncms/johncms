<?php

declare(strict_types=1);

namespace Johncms\Modules\Admin\Infrastructure\Persistence\Repository;

use Illuminate\Database\Eloquent\Builder;
use Johncms\Auth\Authorization\UserRole;
use Johncms\Modules\Forum\Domain\Models\ForumUnread;
use Johncms\Modules\Admin\Domain\Repository\InactiveUsersRepositoryInterface;
use Johncms\Users\User;

final class EloquentInactiveUsersRepository implements InactiveUsersRepositoryInterface
{
    private const MONTH = 2592000;

    public function countInactive(): int
    {
        return $this->inactiveQuery()->count();
    }

    public function getInactiveIds(): array
    {
        return $this->inactiveQuery()->pluck('id')->all();
    }

    public function deleteForumReadMarks(array $userIds): void
    {
        if ($userIds === []) {
            return;
        }

        ForumUnread::query()->whereIn('user_id', $userIds)->delete();
    }

    /**
     * The accounts that registered long ago, have not been seen for months and left next to
     * nothing. An account holding a role is staff, however quiet, and is never one of them.
     *
     * @return Builder<User>
     */
    private function inactiveQuery(): Builder
    {
        $now = time();

        $staff = UserRole::query()
            ->select('user_id')
            ->where(static function (Builder $query) use ($now): void {
                $query->whereNull('expires_at')->orWhere('expires_at', '>', $now);
            });

        $query = User::query()
            ->where('datereg', '<', $now - self::MONTH * 6)
            ->where('lastdate', '<', $now - self::MONTH * 5)
            ->where('postforum', 0)
            ->where('postguest', '<', 10)
            ->where('komm', '<', 10);
        $query->whereNotIn('id', $staff);

        return $query;
    }
}
