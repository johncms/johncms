<?php

declare(strict_types=1);

namespace Johncms\Modules\Album\Infrastructure\Persistence\Repository;

use Illuminate\Database\Eloquent\Builder;
use Johncms\Modules\Album\Domain\Enums\AlbumAccess;
use Johncms\Modules\Album\Domain\Models\Album;
use Johncms\Modules\Album\Domain\Repository\AlbumRepositoryInterface;

final class EloquentAlbumRepository implements AlbumRepositoryInterface
{
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
}
