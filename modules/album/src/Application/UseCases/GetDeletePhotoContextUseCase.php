<?php

declare(strict_types=1);

namespace Johncms\Modules\Album\Application\UseCases;

use Johncms\Modules\Album\Application\Exceptions\AlbumEditForbiddenException;
use Johncms\Modules\Album\Application\Exceptions\AlbumPhotoNotFoundException;
use Johncms\Modules\Album\Domain\Models\AlbumPhoto;
use Johncms\Modules\Album\Domain\Repository\AlbumPhotoRepositoryInterface;
use Johncms\Users\User;

final readonly class GetDeletePhotoContextUseCase
{
    private const MODERATOR_RIGHTS = 6;

    public function __construct(
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
        $isModerator = $this->currentUser->rights >= self::MODERATOR_RIGHTS;
        if (! $isOwner && ! $isModerator) {
            throw new AlbumEditForbiddenException();
        }

        return $photo;
    }
}
