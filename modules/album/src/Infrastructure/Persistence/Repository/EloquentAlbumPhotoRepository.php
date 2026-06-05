<?php

declare(strict_types=1);

namespace Johncms\Modules\Album\Infrastructure\Persistence\Repository;

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
}
