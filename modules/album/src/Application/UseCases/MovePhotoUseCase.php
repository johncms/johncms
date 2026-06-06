<?php

declare(strict_types=1);

namespace Johncms\Modules\Album\Application\UseCases;

use Johncms\Modules\Album\Application\Exceptions\AlbumNotFoundException;
use Johncms\Modules\Album\Domain\Models\AlbumPhoto;
use Johncms\Modules\Album\Domain\Repository\AlbumPhotoRepositoryInterface;
use Johncms\Modules\Album\Domain\Repository\AlbumRepositoryInterface;

final readonly class MovePhotoUseCase
{
    public function __construct(
        private AlbumRepositoryInterface $albumRepository,
        private AlbumPhotoRepositoryInterface $photoRepository,
    ) {
    }

    /**
     * Move the photo to the target album (which must belong to the photo's owner)
     * and return that album's id for redirection.
     */
    public function execute(AlbumPhoto $photo, int $targetAlbumId): int
    {
        $target = $this->albumRepository->findById($targetAlbumId);
        if ($target === null || $target->user_id !== $photo->user_id) {
            throw new AlbumNotFoundException();
        }

        $this->photoRepository->moveToAlbum($photo->id, $target->id, (int) $target->access);

        return $target->id;
    }
}
