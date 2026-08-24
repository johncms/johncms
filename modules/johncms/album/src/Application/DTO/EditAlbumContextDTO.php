<?php

declare(strict_types=1);

namespace Johncms\Modules\Album\Application\DTO;

use Johncms\Modules\Album\Domain\Models\Album;

final readonly class EditAlbumContextDTO
{
    public function __construct(
        public int $ownerId,
        public ?Album $album = null,
    ) {
    }

    public function isEdit(): bool
    {
        return $this->album !== null;
    }
}
