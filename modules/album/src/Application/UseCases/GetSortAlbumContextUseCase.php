<?php

declare(strict_types=1);

namespace Johncms\Modules\Album\Application\UseCases;

use Johncms\Auth\Authorization\AccessCheckerInterface;
use Johncms\Modules\Album\Application\Exceptions\AlbumEditForbiddenException;
use Johncms\Modules\Album\Application\Exceptions\AlbumNotFoundException;
use Johncms\Modules\Album\Application\Services\AlbumPermissions;
use Johncms\Modules\Album\Domain\Models\Album;
use Johncms\Modules\Album\Domain\Repository\AlbumRepositoryInterface;
use Johncms\Users\User;

final readonly class GetSortAlbumContextUseCase
{
    public function __construct(
        private AccessCheckerInterface $accessChecker,
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

        $isOwner = $album->user_id === $this->currentUser->id;
        $canModerate = $this->accessChecker->allows(AlbumPermissions::MODERATE);
        if (! $isOwner && ! $canModerate) {
            throw new AlbumEditForbiddenException();
        }

        return $album;
    }
}
