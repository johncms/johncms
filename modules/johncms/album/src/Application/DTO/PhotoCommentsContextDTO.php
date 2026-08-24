<?php

declare(strict_types=1);

namespace Johncms\Modules\Album\Application\DTO;

final readonly class PhotoCommentsContextDTO
{
    public function __construct(
        public int $photoId,
        public int $albumId,
        public int $ownerId,
        public string $albumName,
        public bool $ownerUnread,
    ) {
    }
}
