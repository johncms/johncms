<?php

declare(strict_types=1);

namespace Johncms\Modules\Profile\Domain\Repository;

interface AlbumPhotoRepositoryInterface
{
    /**
     * Count the photos uploaded by the given user.
     */
    public function countByUser(int $userId): int;
}
