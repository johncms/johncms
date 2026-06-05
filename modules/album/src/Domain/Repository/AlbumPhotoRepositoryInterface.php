<?php

declare(strict_types=1);

namespace Johncms\Modules\Album\Domain\Repository;

interface AlbumPhotoRepositoryInterface
{
    /**
     * Count public photos uploaded after the given timestamp.
     */
    public function countNewPublicSince(int $time): int;
}
