<?php

declare(strict_types=1);

namespace Johncms\Modules\Admin\Infrastructure\Persistence\Repository;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Johncms\Auth\Events\AuthEvent;
use Johncms\Modules\Admin\Domain\Repository\AuthLogRepositoryInterface;
use Johncms\Users\User;

final class EloquentAuthLogRepository implements AuthLogRepositoryInterface
{
    public function count(?int $userId = null, ?string $event = null): int
    {
        return $this->query($userId, $event)->count();
    }

    /**
     * @return Collection<int, AuthEvent>
     */
    public function get(int $limit, int $offset, ?int $userId = null, ?string $event = null): Collection
    {
        return $this->query($userId, $event)
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
    private function query(?int $userId, ?string $event): Builder
    {
        $query = AuthEvent::query();

        if ($userId !== null) {
            // Both columns on purpose: what was done to the account and what its owner did to
            // somebody else are equally part of "everything about this user".
            $query->where(
                static function (Builder $nested) use ($userId): void {
                    $nested->where('user_id', '=', $userId)->orWhere('actor_id', '=', $userId);
                }
            );
        }

        if ($event !== null) {
            $query->where('event', '=', $event);
        }

        return $query;
    }
}
