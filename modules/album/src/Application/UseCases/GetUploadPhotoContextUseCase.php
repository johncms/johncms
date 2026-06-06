<?php

declare(strict_types=1);

namespace Johncms\Modules\Album\Application\UseCases;

use Johncms\Modules\Album\Application\Exceptions\AlbumEditForbiddenException;
use Johncms\Modules\Album\Application\Exceptions\AlbumNotFoundException;
use Johncms\Modules\Album\Domain\Models\Album;
use Johncms\Modules\Album\Domain\Repository\AlbumRepositoryInterface;
use Johncms\Users\User;

final readonly class GetUploadPhotoContextUseCase
{
    private const ADMIN_RIGHTS = 7;

    public function __construct(
        private AlbumRepositoryInterface $albumRepository,
        private User $currentUser,
    ) {
    }

    public function execute(int $albumId): Album
    {
        $album = $this->albumRepository->findById($albumId);
        if ($album === null) {
            throw new AlbumNotFoundException();
        }

        $isOwner = $album->user_id === $this->currentUser->id && empty($this->currentUser->ban);
        $isAdmin = $this->currentUser->rights >= self::ADMIN_RIGHTS;
        if (! $isOwner && ! $isAdmin) {
            throw new AlbumEditForbiddenException();
        }

        return $album;
    }
}
