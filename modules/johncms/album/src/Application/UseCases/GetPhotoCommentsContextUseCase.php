<?php

declare(strict_types=1);

namespace Johncms\Modules\Album\Application\UseCases;

use Johncms\Modules\Album\Application\DTO\PhotoCommentsContextDTO;
use Johncms\Modules\Album\Application\Exceptions\AlbumPhotoNotFoundException;
use Johncms\Modules\Album\Domain\Repository\AlbumPhotoRepositoryInterface;

/**
 * Loads a photo for its comments page and guards album access.
 *
 * Access matches the legacy comments flow (same policy as show: admin bypass),
 * so a locked private/password album surfaces as access denied.
 */
final readonly class GetPhotoCommentsContextUseCase
{
    public function __construct(
        private AlbumPhotoRepositoryInterface $photoRepository,
        private EnsureAlbumAccessUseCase $ensureAccess,
    ) {
    }

    public function execute(int $photoId): PhotoCommentsContextDTO
    {
        $photo = $this->photoRepository->findById($photoId);
        if ($photo === null || $photo->album === null) {
            throw new AlbumPhotoNotFoundException();
        }

        $this->ensureAccess->execute($photo->album);

        return new PhotoCommentsContextDTO(
            photoId: $photo->id,
            albumId: $photo->album_id,
            ownerId: $photo->user_id,
            albumName: $photo->album->name,
            ownerUnread: $photo->unread_comments,
        );
    }
}
