<?php

declare(strict_types=1);

namespace Johncms\Modules\Album\Application\UseCases;

use Johncms\Modules\Album\Application\Exceptions\AlbumPhotoNotFoundException;
use Johncms\Modules\Album\Domain\Models\AlbumPhoto;
use Johncms\Modules\Album\Domain\Repository\AlbumPhotoRepositoryInterface;

final readonly class GetVotePhotoContextUseCase
{
    public function __construct(
        private AlbumPhotoRepositoryInterface $photoRepository,
    ) {
    }

    public function execute(int $photoId): AlbumPhoto
    {
        $photo = $this->photoRepository->findById($photoId);
        if ($photo === null) {
            throw new AlbumPhotoNotFoundException();
        }

        return $photo;
    }
}
