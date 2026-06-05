<?php

declare(strict_types=1);

namespace Johncms\Modules\Album\Infrastructure\Persistence\Repository;

use Illuminate\Database\Query\Builder as QueryBuilder;
use Johncms\Modules\Album\Domain\Enums\AlbumAccess;
use Johncms\Modules\Album\Domain\Models\AlbumPhoto;
use Johncms\Modules\Album\Domain\Repository\AlbumPhotoRepositoryInterface;

final class EloquentAlbumPhotoRepository implements AlbumPhotoRepositoryInterface
{
    public function countNewPublicSince(int $time): int
    {
        return AlbumPhoto::query()
            ->where('time', '>', $time)
            ->where('access', AlbumAccess::Public->value)
            ->count();
    }

    public function countByUsers(array $userIds, ?int $restrictToVisibleForUser): array
    {
        if ($userIds === []) {
            return [];
        }

        $query = AlbumPhoto::query()
            ->select('user_id')
            ->selectRaw('COUNT(*) AS aggregate')
            ->whereIn('user_id', $userIds);

        if ($restrictToVisibleForUser !== null) {
            $query->where(static function (QueryBuilder $builder) use ($restrictToVisibleForUser): void {
                $builder
                    ->whereIn('access', [AlbumAccess::Password->value, AlbumAccess::Public->value])
                    ->orWhere('user_id', $restrictToVisibleForUser);
            });
        }

        return $query->groupBy('user_id')->pluck('aggregate', 'user_id')->all();
    }
}
