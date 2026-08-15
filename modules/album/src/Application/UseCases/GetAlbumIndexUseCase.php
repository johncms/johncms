<?php

declare(strict_types=1);

namespace Johncms\Modules\Album\Application\UseCases;

use Johncms\Auth\Authorization\AccessCheckerInterface;
use Johncms\Modules\Album\Application\DTO\AlbumIndexDTO;
use Johncms\Modules\Album\Application\Services\AlbumPermissions;
use Johncms\Modules\Album\Domain\Repository\AlbumPhotoRepositoryInterface;
use Johncms\Modules\Album\Domain\Repository\AlbumRepositoryInterface;
use Johncms\Users\User;

final readonly class GetAlbumIndexUseCase
{
    private const NEW_PHOTO_PERIOD = 259200; // 3 days

    public function __construct(
        private AccessCheckerInterface $accessChecker,
        private AlbumRepositoryInterface $albumRepository,
        private AlbumPhotoRepositoryInterface $albumPhotoRepository,
        private User $currentUser,
    ) {
    }

    public function execute(): AlbumIndexDTO
    {
        // Moderators see all albums; regular users only see non-private albums and their own.
        $restrictForUser = $this->accessChecker->allows(AlbumPermissions::MODERATE)
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
