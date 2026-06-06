<?php

declare(strict_types=1);

namespace Johncms\Modules\Album\Infrastructure\Persistence\Repository;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Johncms\Modules\Album\Domain\Enums\AlbumAccess;
use Johncms\Modules\Album\Domain\Models\Album;
use Johncms\Modules\Album\Domain\Repository\AlbumRepositoryInterface;
use Johncms\Users\User;

final class EloquentAlbumRepository implements AlbumRepositoryInterface
{
    public function findUserById(int $userId): ?User
    {
        return User::query()->find($userId);
    }

    public function findById(int $albumId): ?Album
    {
        return Album::query()->find($albumId);
    }

    public function getUserAlbums(int $userId, ?int $restrictToVisibleForUser): Collection
    {
        $query = Album::query()
            ->where('user_id', $userId)
            ->withCount('photos')
            ->orderBy('sort');
        $this->applyVisibility($query->getQuery(), $restrictToVisibleForUser);

        return $query->get();
    }

    public function countOwnersBySex(string $sex, ?int $restrictToVisibleForUser): int
    {
        $query = Album::query()
            ->join('users', 'users.id', '=', 'cms_album_cat.user_id')
            ->where('users.sex', $sex);

        if ($restrictToVisibleForUser !== null) {
            $query->where(static function (Builder $builder) use ($restrictToVisibleForUser): void {
                $builder
                    ->whereIn('cms_album_cat.access', [AlbumAccess::Password->value, AlbumAccess::Public->value])
                    ->orWhere('cms_album_cat.user_id', $restrictToVisibleForUser);
            });
        }

        return $query->distinct()->count('cms_album_cat.user_id');
    }

    public function paginateOwnersBySex(
        ?string $sex,
        ?int $restrictToVisibleForUser,
        int $page,
        int $perPage
    ): LengthAwarePaginator {
        $paginator = User::query()
            ->when(
                $sex !== null,
                static fn (Builder $query): Builder => $query->where('sex', $sex),
                static fn (Builder $query): Builder => $query->where('sex', '<>', '')
            )
            ->whereExists(function (QueryBuilder $query) use ($restrictToVisibleForUser): void {
                $query->selectRaw('1')
                    ->from('cms_album_cat')
                    ->whereColumn('cms_album_cat.user_id', 'users.id');
                $this->applyVisibility($query, $restrictToVisibleForUser);
            })
            ->orderBy('name')
            ->paginate($perPage, ['id', 'name', 'lastdate'], 'page', $page);

        $userIds = array_map(static fn (User $user): int => $user->id, $paginator->items());
        $albumCounts = $this->countAlbumsByUsers($userIds, $restrictToVisibleForUser);
        foreach ($paginator->items() as $user) {
            $user->count_albums = $albumCounts[$user->id] ?? 0;
        }

        return $paginator;
    }

    /**
     * @param list<int> $userIds
     * @return array<int, int>
     */
    private function countAlbumsByUsers(array $userIds, ?int $restrictToVisibleForUser): array
    {
        if ($userIds === []) {
            return [];
        }

        $query = Album::query()
            ->select('user_id')
            ->selectRaw('COUNT(*) AS aggregate')
            ->whereIn('user_id', $userIds);
        $this->applyVisibility($query->getQuery(), $restrictToVisibleForUser);

        return $query->groupBy('user_id')->pluck('aggregate', 'user_id')->all();
    }

    public function existsByNameForUser(int $userId, string $name): bool
    {
        return Album::query()
            ->where('user_id', $userId)
            ->where('name', $name)
            ->exists();
    }

    public function create(int $userId, string $name, string $description, ?string $password, int $access): Album
    {
        $maxSort = Album::query()->where('user_id', $userId)->max('sort');
        $sort = $maxSort !== null ? (int) $maxSort + 1 : 1;

        $album = new Album();
        $album->user_id = $userId;
        $album->name = $name;
        $album->description = $description;
        $album->password = $password;
        $album->access = $access;
        $album->sort = $sort;
        $album->save();

        return $album;
    }

    public function update(Album $album, string $name, string $description, ?string $password, int $access): void
    {
        $album->name = $name;
        $album->description = $description;
        $album->password = $password;
        $album->access = $access;
        $album->save();
    }

    public function delete(Album $album): void
    {
        $album->delete();
    }

    private function applyVisibility(QueryBuilder $query, ?int $restrictToVisibleForUser): void
    {
        if ($restrictToVisibleForUser === null) {
            return;
        }

        $query->where(static function (QueryBuilder $builder) use ($restrictToVisibleForUser): void {
            $builder
                ->whereIn('access', [AlbumAccess::Password->value, AlbumAccess::Public->value])
                ->orWhere('user_id', $restrictToVisibleForUser);
        });
    }
}
