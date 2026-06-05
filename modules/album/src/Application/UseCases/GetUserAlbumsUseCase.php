<?php

declare(strict_types=1);

namespace Johncms\Modules\Album\Application\UseCases;

use Johncms\Modules\Album\Application\DTO\UserAlbumsResultDTO;
use Johncms\Modules\Album\Application\Exceptions\AlbumOwnerNotFoundException;
use Johncms\Modules\Album\Domain\Repository\AlbumRepositoryInterface;
use Johncms\Users\User;

final readonly class GetUserAlbumsUseCase
{
    private const MODERATOR_RIGHTS = 6;
    private const ADMIN_RIGHTS = 7;
    private const MAX_ALBUMS = 20;

    public function __construct(
        private AlbumRepositoryInterface $albumRepository,
        private User $currentUser,
    ) {
    }

    public function execute(int $userId): UserAlbumsResultDTO
    {
        $owner = $this->albumRepository->findUserById($userId);
        if ($owner === null) {
            throw new AlbumOwnerNotFoundException();
        }

        $isOwner = $owner->id === $this->currentUser->id;
        $isModerator = $this->currentUser->rights >= self::MODERATOR_RIGHTS;
        $notBanned = empty($this->currentUser->ban);

        // Moderators see all albums; regular users only see non-private albums and their own.
        $restrictForViewer = $isModerator ? null : $this->currentUser->id;
        $albums = $this->albumRepository->getUserAlbums($userId, $restrictForViewer);

        $canManage = ($isOwner && $notBanned) || $isModerator;
        $canCreate = ($isOwner && $notBanned && $albums->count() < self::MAX_ALBUMS)
            || $this->currentUser->rights >= self::ADMIN_RIGHTS;

        return new UserAlbumsResultDTO(
            owner: $owner,
            albums: $albums,
            canCreate: $canCreate,
            canManage: $canManage,
        );
    }
}
