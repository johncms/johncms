<?php

declare(strict_types=1);

namespace Johncms\Modules\Album\Application\DTO;

final readonly class PhotoPageResultDTO
{
    public function __construct(
        public int $albumId,
        public int $ownerId,
        public ?PhotoDetailDTO $photo,
        public int $total,
        public int $offset,
        public string $successMessage,
    ) {
    }
}
