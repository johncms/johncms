<?php

declare(strict_types=1);

namespace Johncms\Modules\Album\Application\UseCases;

use Johncms\Auth\Authorization\AccessCheckerInterface;
use Johncms\Auth\CurrentUser;
use Johncms\Modules\Album\Application\DTO\UserAlbumsResultDTO;
use Johncms\Modules\Album\Application\Exceptions\AlbumOwnerNotFoundException;
use Johncms\Modules\Album\Application\Services\AlbumPermissions;
use Johncms\Modules\Album\Domain\Repository\AlbumRepositoryInterface;
use Johncms\Users\User;

final readonly class GetUserAlbumsUseCase
{
    private const MAX_ALBUMS = 20;

    public function __construct(
        private AccessCheckerInterface $accessChecker,
        private AlbumRepositoryInterface $albumRepository,
        private CurrentUser $currentUser,
    ) {
    }

    public function execute(int $userId): UserAlbumsResultDTO
    {
        $owner = $this->albumRepository->findUserById($userId);
        if ($owner === null) {
            throw new AlbumOwnerNotFoundException();
        }

        $isOwner = $owner->id === $this->currentUser->id();
        $canModerate = $this->accessChecker->allows(AlbumPermissions::MODERATE);
        $notBanned = empty($this->currentUser->user()->ban);

        // Moderators see all albums; regular users only see non-private albums and their own.
        $restrictForViewer = $canModerate ? null : $this->currentUser->id();
        $albums = $this->albumRepository->getUserAlbums($userId, $restrictForViewer);

        $canManage = ($isOwner && $notBanned) || $canModerate;
        $canCreate = ($isOwner && $notBanned && $albums->count() < self::MAX_ALBUMS)
            || $canModerate;

        return new UserAlbumsResultDTO(
            owner: $owner,
            albums: $albums,
            canCreate: $canCreate,
            canManage: $canManage,
        );
    }
}
