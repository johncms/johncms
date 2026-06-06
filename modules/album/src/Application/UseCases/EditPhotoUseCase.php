<?php

declare(strict_types=1);

namespace Johncms\Modules\Album\Application\UseCases;

use Johncms\Modules\Album\Domain\Models\AlbumPhoto;
use Johncms\Modules\Album\Domain\Repository\AlbumPhotoRepositoryInterface;

final readonly class EditPhotoUseCase
{
    private const MAX_DESCRIPTION_LENGTH = 1500;

    public function __construct(
        private AlbumPhotoRepositoryInterface $photoRepository,
    ) {
    }

    public function execute(AlbumPhoto $photo, string $description): void
    {
        $description = mb_substr(trim($description), 0, self::MAX_DESCRIPTION_LENGTH);
        $this->photoRepository->updateDescription($photo->id, $description);
    }
}
