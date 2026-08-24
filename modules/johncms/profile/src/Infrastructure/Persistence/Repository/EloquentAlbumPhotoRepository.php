<?php

declare(strict_types=1);

namespace Johncms\Modules\Profile\Infrastructure\Persistence\Repository;

use Illuminate\Database\Capsule\Manager as Capsule;
use Johncms\Modules\Profile\Domain\Repository\AlbumPhotoRepositoryInterface;

final class EloquentAlbumPhotoRepository implements AlbumPhotoRepositoryInterface
{
    public function countByUser(int $userId): int
    {
        return Capsule::table('cms_album_files')
            ->where('user_id', '=', $userId)
            ->count();
    }
}
