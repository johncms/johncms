<?php

declare(strict_types=1);

namespace Johncms\Modules\Admin\Infrastructure\Persistence\Repository;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Johncms\Auth\Events\AuthEvent;
use Johncms\Modules\Admin\Domain\Repository\AuthLogRepositoryInterface;
use Johncms\Modules\Admin\Domain\ValueObjects\AuthLogFilter;
use Johncms\Users\User;

final class EloquentAuthLogRepository implements AuthLogRepositoryInterface
{
    public function count(AuthLogFilter $filter, int $now): int
    {
        return $this->query($filter, $now)->count();
    }

    /**
     * @return Collection<int, AuthEvent>
     */
    public function get(AuthLogFilter $filter, int $now, int $limit, int $offset): Collection
    {
        return $this->query($filter, $now)
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->offset($offset)
            ->limit($limit)
            ->get();
    }

    public function namesOf(array $ids): array
    {
        if ($ids === []) {
            return [];
        }

        /** @var array<int, string> $names */
        $names = User::query()
            ->whereIn('id', $ids)
            ->pluck('name', 'id')
            ->all();

        return $names;
    }

    /**
     * @return Builder<AuthEvent>
     */
    private function query(AuthLogFilter $filter, int $now): Builder
    {
        $query = AuthEvent::query();

        if ($filter->userId !== null) {
            $this->about($query, [$filter->userId]);
        }

        if ($filter->search !== '') {
            $this->search($query, $filter->search);
        }

        if ($filter->event !== null) {
            $query->where('event', '=', $filter->event);
        }

        $after = $filter->period->after($now);
        if ($after !== null) {
            $query->where('created_at', '>', $after);
        }

        return $query;
    }

    /**
     * The entries about the accounts: what was done to them and what they did to somebody else,
     * since both are part of "everything about this user".
     *
     * @param Builder<AuthEvent>     $query
     * @param list<int>|QueryBuilder $ids    The numbers, or a query selecting them.
     */
    private function about(Builder $query, array|QueryBuilder $ids): void
    {
        $query->where(
            static function (Builder $nested) use ($ids): void {
                $nested->whereIn('user_id', $ids)->orWhereIn('actor_id', $ids);
            }
        );
    }

    /**
     * An address the entry came from, an account by its number, or accounts by any part of their
     * nickname.
     *
     * @param Builder<AuthEvent> $query
     */
    private function search(Builder $query, string $search): void
    {
        if (filter_var($search, FILTER_VALIDATE_IP) !== false) {
            $query->where('ip', '=', $search);

            return;
        }

        if (ctype_digit($search)) {
            $this->about($query, [(int) $search]);

            return;
        }

        $this->about($query, User::query()->toBase()->select('id')->where('name', 'like', '%' . addcslashes($search, '%_\\') . '%'));
    }
}
