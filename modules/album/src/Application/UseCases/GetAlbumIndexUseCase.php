<?php

declare(strict_types=1);

namespace Johncms\Modules\Album\Application\UseCases;

use Johncms\Modules\Album\Application\DTO\AlbumIndexDTO;
use Johncms\Modules\Album\Domain\Repository\AlbumPhotoRepositoryInterface;
use Johncms\Modules\Album\Domain\Repository\AlbumRepositoryInterface;
use Johncms\Users\User;

final readonly class GetAlbumIndexUseCase
{
    private const MODERATOR_RIGHTS = 6;
    private const NEW_PHOTO_PERIOD = 259200; // 3 days

    public function __construct(
        private AlbumRepositoryInterface $albumRepository,
        private AlbumPhotoRepositoryInterface $albumPhotoRepository,
        private User $currentUser,
    ) {
    }

    public function execute(): AlbumIndexDTO
    {
        // Moderators see all albums; regular users only see non-private albums and their own.
        $restrictForUser = $this->currentUser->rights >= self::MODERATOR_RIGHTS
            ? null
            : $this->currentUser->id;

        $men = $this->albumRepository->countOwnersBySex('m', $restrictForUser);
        $women = $this->albumRepository->countOwnersBySex('zh', $restrictForUser);

        return new AlbumIndexDTO(
            men: $men,
            women: $women,
            albums: $men + $women,
            newPhotos: $this->albumPhotoRepository->countNewPublicSince(time() - self::NEW_PHOTO_PERIOD),
        );
    }
}
