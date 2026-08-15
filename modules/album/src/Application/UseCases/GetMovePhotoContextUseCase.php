<?php

declare(strict_types=1);

namespace Johncms\Modules\Album\Application\UseCases;

use Johncms\Auth\Authorization\AccessCheckerInterface;
use Johncms\Modules\Album\Application\Exceptions\AlbumEditForbiddenException;
use Johncms\Modules\Album\Application\Exceptions\AlbumPhotoNotFoundException;
use Johncms\Modules\Album\Application\Services\AlbumPermissions;
use Johncms\Modules\Album\Domain\Models\AlbumPhoto;
use Johncms\Modules\Album\Domain\Repository\AlbumPhotoRepositoryInterface;
use Johncms\Users\User;

final readonly class GetMovePhotoContextUseCase
{
    public function __construct(
        private AccessCheckerInterface $accessChecker,
        private AlbumPhotoRepositoryInterface $photoRepository,
        private User $currentUser,
    ) {
    }

    public function execute(int $photoId): AlbumPhoto
    {
        $photo = $this->photoRepository->findById($photoId);
        if ($photo === null) {
            throw new AlbumPhotoNotFoundException();
        }

        $isOwner = $photo->user_id === $this->currentUser->id;
        $canModerate = $this->accessChecker->allows(AlbumPermissions::MODERATE);
        if (! $isOwner && ! $canModerate) {
            throw new AlbumEditForbiddenException();
        }

        return $photo;
    }
}
