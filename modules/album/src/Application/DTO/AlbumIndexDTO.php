<?php

declare(strict_types=1);

namespace Johncms\Modules\Album\Application\DTO;

final readonly class AlbumIndexDTO
{
    public function __construct(
        public int $men,
        public int $women,
        public int $albums,
        public int $newPhotos,
    ) {
    }
}
