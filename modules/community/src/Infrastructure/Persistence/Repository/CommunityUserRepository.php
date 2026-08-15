<?php

declare(strict_types=1);

namespace Johncms\Modules\Community\Infrastructure\Persistence\Repository;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Johncms\Auth\Authorization\UserRole;
use Johncms\Auth\Schema\AuthSchema;
use Johncms\Modules\Community\Domain\Repository\CommunityUserRepositoryInterface;
use Johncms\Users\User;

final class CommunityUserRepository implements CommunityUserRepositoryInterface
{
    public function countAdministrationUsers(): int
    {
        return $this->administration(User::query())->count();
    }

    public function countBirthdayUsers(int $day, int $month): int
    {
        return User::query()
            ->where('dayb', '=', $day)
            ->where('monthb', '=', $month)
            ->where('preg', '=', 1)
            ->toBase()
            ->count();
    }

    public function countApprovedAdministrationUsers(): int
    {
        return $this->administration(User::query()->approved())->count();
    }

    /**
     * @return Collection<int, User>
     */
    public function getApprovedAdministrationUsers(int $limit, int $offset): Collection
    {
        $query = $this->administration(User::query()->approved());
        $query->select('users.*');
        // The highest role of the account as a column of its own: the list is ordered by it, and
        // a join would multiply the rows of anybody holding more than one role.
        $query->selectSub($this->highestLevel(), 'staff_level');

        return $query
            ->orderByDesc('staff_level')
            ->offset($offset)
            ->limit($limit)
            ->get();
    }

    /**
     * The staff of the site: whoever holds a role that was granted to them and has not run out.
     * The roles applied to everybody by default are not a grant, which is what keeps the whole
     * list of accounts out of this.
     *
     * @param Builder<User> $query
     * @return Builder<User>
     */
    private function administration(Builder $query): Builder
    {
        $query->whereExists($this->grants()->toBase());

        return $query;
    }

    /**
     * The grants of the account the outer query is looking at, still in force.
     *
     * @return Builder<UserRole>
     */
    private function grants(): Builder
    {
        $now = time();

        /** @var Builder<UserRole> $query */
        $query = UserRole::query();
        $query->whereColumn(AuthSchema::USER_ROLES . '.user_id', 'users.id');
        $query->where(
            static function (Builder $builder) use ($now): void {
                $builder->whereNull(AuthSchema::USER_ROLES . '.expires_at')
                    ->orWhere(AuthSchema::USER_ROLES . '.expires_at', '>', $now);
            }
        );

        return $query;
    }

    /**
     * @return Builder<UserRole>
     */
    private function highestLevel(): Builder
    {
        $query = $this->grants();
        $query->join(AuthSchema::ROLES, AuthSchema::ROLES . '.id', '=', AuthSchema::USER_ROLES . '.role_id');
        $query->selectRaw('MAX(' . AuthSchema::ROLES . '.level)');

        return $query;
    }

    public function countApprovedUsers(): int
    {
        return User::query()
            ->approved()
            ->count();
    }

    /**
     * @return Collection<int, User>
     */
    public function getApprovedUsers(int $limit, int $offset): Collection
    {
        return User::query()
            ->approved()
            ->offset($offset)
            ->limit($limit)
            ->get();
    }

    public function countApprovedBirthdayUsers(int $day, int $month): int
    {
        return User::query()
            ->approved()
            ->where('dayb', '=', $day)
            ->where('monthb', '=', $month)
            ->count();
    }

    /**
     * @return Collection<int, User>
     */
    public function getApprovedBirthdayUsers(int $limit, int $offset, int $day, int $month): Collection
    {
        return User::query()
            ->approved()
            ->where('dayb', '=', $day)
            ->where('monthb', '=', $month)
            ->offset($offset)
            ->limit($limit)
            ->get();
    }

    public function countUsersByLatinNameLike(string $searchLike): int
    {
        return User::query()
            ->approved()
            ->where('name_lat', 'LIKE', $searchLike)
            ->count();
    }

    /**
     * @return Collection<int, User>
     */
    public function getUsersByLatinNameLike(int $limit, int $offset, string $searchLike): Collection
    {
        return User::query()
            ->approved()
            ->where('name_lat', 'LIKE', $searchLike)
            ->orderBy('name')
            ->offset($offset)
            ->limit($limit)
            ->get();
    }

    public function getTopForumUsers(int $limit): Collection
    {
        return User::query()
            ->where('postforum', '>', 0)
            ->orderByDesc('postforum')
            ->limit($limit)
            ->get();
    }

    public function getTopGuestbookUsers(int $limit): Collection
    {
        return User::query()
            ->where('postguest', '>', 0)
            ->orderByDesc('postguest')
            ->limit($limit)
            ->get();
    }

    public function getTopCommentUsers(int $limit): Collection
    {
        return User::query()
            ->where('komm', '>', 0)
            ->orderByDesc('komm')
            ->limit($limit)
            ->get();
    }

    public function getTopKarmaUsers(int $limit): Collection
    {
        return User::query()
            ->selectRaw('*, (`karma_plus` - `karma_minus`) as `karma`')
            ->whereRaw('(`karma_plus` - `karma_minus`) > 0')
            ->orderByDesc('karma')
            ->limit($limit)
            ->get();
    }
}
