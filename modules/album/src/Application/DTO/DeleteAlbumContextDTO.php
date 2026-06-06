<?php

declare(strict_types=1);

namespace Johncms\Modules\Album\Application\DTO;

use Johncms\Modules\Album\Domain\Models\Album;

final readonly class DeleteAlbumContextDTO
{
    public function __construct(
        public Album $album,
    ) {
    }
}
