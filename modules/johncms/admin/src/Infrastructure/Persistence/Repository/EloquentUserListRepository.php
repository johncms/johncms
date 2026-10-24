<?php

declare(strict_types=1);

namespace Johncms\Modules\Admin\Infrastructure\Persistence\Repository;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Johncms\Auth\Authorization\UserRole;
use Johncms\Modules\Admin\Domain\Enums\UserListSort;
use Johncms\Modules\Admin\Domain\Enums\UserListStatus;
use Johncms\Modules\Admin\Domain\Repository\UserListRepositoryInterface;
use Johncms\Modules\Admin\Domain\ValueObjects\UserListFilter;
use Johncms\Users\Ban;
use Johncms\Users\User;

final class EloquentUserListRepository implements UserListRepositoryInterface
{
    public function findById(int $id): ?User
    {
        return User::query()->find($id);
    }

    public function count(UserListFilter $filter, int $now): int
    {
        return $this->filtered($filter, $now)->count();
    }

    /**
     * @return Collection<int, User>
     */
    public function getPage(UserListFilter $filter, int $now, int $limit, int $offset): Collection
    {
        $query = $this->filtered($filter, $now);
        $direction = $filter->direction->value;

        // The direction comes from an enum and the karma has no column of its own, so the raw
        // expression carries nothing from the address.
        match ($filter->sort) {
            UserListSort::ID         => $query->orderBy('id', $direction),
            UserListSort::NICK       => $query->orderBy('name', $direction),
            UserListSort::IP         => $query->orderBy('ip', $direction),
            UserListSort::LAST_VISIT => $query->orderBy('lastdate', $direction),
            UserListSort::REGISTERED => $query->orderBy('datereg', $direction),
            UserListSort::KARMA      => $query->orderByRaw('(karma_plus - karma_minus) ' . $direction),
        };

        if ($filter->sort !== UserListSort::ID) {
            $query->orderBy('id', $direction);
        }

        return $query->offset($offset)->limit($limit)->get();
    }

    public function getBanEnds(array $userIds, int $now): array
    {
        if ($userIds === []) {
            return [];
        }

        return Ban::query()
            ->whereIn('user_id', $userIds)
            ->where('ban_time', '>', $now)
            ->groupBy('user_id')
            ->selectRaw('user_id, MAX(ban_time) AS ends')
            ->pluck('ends', 'user_id')
            ->map(static fn (mixed $end): int => (int) $end)
            ->all();
    }

    /**
     * @return Builder<User>
     */
    private function filtered(UserListFilter $filter, int $now): Builder
    {
        $query = User::query();

        if ($filter->search !== '') {
            $this->search($query, $filter->search);
        }

        match ($filter->status) {
            UserListStatus::PENDING => $query->where('preg', 0),
            UserListStatus::BANNED  => $query->whereIn('id', Ban::query()->select('user_id')->where('ban_time', '>', $now)),
            UserListStatus::ALL     => null,
        };

        if ($filter->staff || $filter->roleId !== null) {
            $grants = $this->grantsInForce($now)->select('user_id');

            if ($filter->roleId !== null) {
                $grants->where('role_id', $filter->roleId);
            }

            $query->whereIn('id', $grants);
        }

        $seenAfter = $filter->activity->seenAfter($now);
        if ($seenAfter !== null) {
            $query->where('lastdate', '>', $seenAfter);
        }

        $seenBefore = $filter->activity->seenBefore($now);
        if ($seenBefore !== null) {
            $query->where('lastdate', '<', $seenBefore);
        }

        $registeredAfter = $filter->registered->registeredAfter($now);
        if ($registeredAfter !== null) {
            $query->where('datereg', '>', $registeredAfter);
        }

        return $query;
    }

    /**
     * A nickname or an address by any part of it, an account by its number, a visitor by the
     * IPv4 address they came from.
     *
     * @param Builder<User> $query
     */
    private function search(Builder $query, string $search): void
    {
        $like = '%' . addcslashes($search, '%_\\') . '%';
        $ip = filter_var($search, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) !== false ? ip2long($search) : false;

        $query->where(
            static function (Builder $query) use ($search, $like, $ip): void {
                $query->where('name', 'like', $like)->orWhere('mail', 'like', $like);

                if (ctype_digit($search)) {
                    $query->orWhere('id', (int) $search);
                }

                if ($ip !== false) {
                    $query->orWhere('ip', $ip)->orWhere('ip_via_proxy', $ip);
                }
            }
        );
    }

    /**
     * The grants that have not run out: temporary moderation stops counting on its own.
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
