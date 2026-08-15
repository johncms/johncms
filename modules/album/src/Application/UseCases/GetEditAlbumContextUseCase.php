<?php

declare(strict_types=1);

namespace Johncms\Modules\Album\Application\UseCases;

use Johncms\Auth\Authorization\AccessCheckerInterface;
use Johncms\Modules\Album\Application\DTO\EditAlbumContextDTO;
use Johncms\Modules\Album\Application\Exceptions\AlbumEditForbiddenException;
use Johncms\Modules\Album\Application\Exceptions\AlbumNotFoundException;
use Johncms\Modules\Album\Application\Exceptions\AlbumOwnerNotFoundException;
use Johncms\Modules\Album\Application\Services\AlbumPermissions;
use Johncms\Modules\Album\Domain\Repository\AlbumRepositoryInterface;
use Johncms\Users\User;

final readonly class GetEditAlbumContextUseCase
{
    public function __construct(
        private AccessCheckerInterface $accessChecker,
        private AlbumRepositoryInterface $albumRepository,
        private User $currentUser,
    ) {
    }

    /**
     * Resolve the context for creating a new album for the given user.
     */
    public function forCreate(int $ownerId): EditAlbumContextDTO
    {
        if ($this->albumRepository->findUserById($ownerId) === null) {
            throw new AlbumOwnerNotFoundException();
        }

        $this->ensureCanManage($ownerId);

        return new EditAlbumContextDTO(ownerId: $ownerId);
    }

    /**
     * Resolve the context for editing an existing album.
     */
    public function forEdit(int $albumId): EditAlbumContextDTO
    {
        $album = $this->albumRepository->findById($albumId);
        if ($album === null) {
            throw new AlbumNotFoundException();
        }

        $this->ensureCanManage($album->user_id);

        return new EditAlbumContextDTO(ownerId: $album->user_id, album: $album);
    }

    private function ensureCanManage(int $ownerId): void
    {
        $isOwner = $ownerId === $this->currentUser->id && empty($this->currentUser->ban);
        $canModerate = $this->accessChecker->allows(AlbumPermissions::MODERATE);

        if (! $isOwner && ! $canModerate) {
            throw new AlbumEditForbiddenException();
        }
    }
}
