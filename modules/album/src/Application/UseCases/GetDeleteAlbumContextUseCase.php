<?php

declare(strict_types=1);

namespace Johncms\Modules\Album\Application\UseCases;

use Johncms\Modules\Album\Application\DTO\DeleteAlbumContextDTO;
use Johncms\Modules\Album\Application\Exceptions\AlbumEditForbiddenException;
use Johncms\Modules\Album\Application\Exceptions\AlbumNotFoundException;
use Johncms\Modules\Album\Domain\Repository\AlbumRepositoryInterface;
use Johncms\Users\User;

final readonly class GetDeleteAlbumContextUseCase
{
    private const MODERATOR_RIGHTS = 6;

    public function __construct(
        private AlbumRepositoryInterface $albumRepository,
        private User $currentUser,
    ) {
    }

    public function execute(int $albumId): DeleteAlbumContextDTO
    {
        $album = $this->albumRepository->findById($albumId);
        if ($album === null) {
            throw new AlbumNotFoundException();
        }

        $isOwner = $album->user_id === $this->currentUser->id;
        $isModerator = $this->currentUser->rights >= self::MODERATOR_RIGHTS;
        if (! $isOwner && ! $isModerator) {
            throw new AlbumEditForbiddenException();
        }

        return new DeleteAlbumContextDTO(album: $album);
    }
}
