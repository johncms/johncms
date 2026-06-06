<?php

declare(strict_types=1);

namespace Johncms\Modules\Album\Application\DTO;

final readonly class AlbumViewResultDTO
{
    /**
     * @param list<PhotoViewDTO> $photos
     */
    public function __construct(
        public int $albumId,
        public int $ownerId,
        public string $albumName,
        public array $photos,
        public int $total,
        public bool $hasAddPhoto,
    ) {
    }
}
