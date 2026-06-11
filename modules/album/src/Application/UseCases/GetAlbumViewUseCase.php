<?php

declare(strict_types=1);

namespace Johncms\Modules\Album\Application\UseCases;

use Johncms\Modules\Album\Application\DTO\AlbumViewResultDTO;
use Johncms\Modules\Album\Application\Exceptions\AlbumNotFoundException;
use Johncms\Modules\Album\Application\Services\PhotoPresenter;
use Johncms\Modules\Album\Domain\Models\Album;
use Johncms\Modules\Album\Domain\Models\AlbumPhoto;
use Johncms\Modules\Album\Domain\Repository\AlbumPhotoRepositoryInterface;
use Johncms\Modules\Album\Domain\Repository\AlbumRepositoryInterface;
use Johncms\Modules\Album\Domain\Repository\AlbumVoteRepositoryInterface;
use Johncms\Users\User;

final readonly class GetAlbumViewUseCase
{
    private const ADMIN_RIGHTS = 7;
    private const VOTE_MIN_POSTS = 5;
    private const VOTE_MIN_AGE = 259200; // 3 days since registration

    public function __construct(
        private AlbumRepositoryInterface $albumRepository,
        private AlbumPhotoRepositoryInterface $photoRepository,
        private AlbumVoteRepositoryInterface $voteRepository,
        private EnsureAlbumAccessUseCase $ensureAccess,
        private PhotoPresenter $photoPresenter,
        private User $currentUser,
    ) {
    }

    public function count(int $albumId, ?string $submittedPassword): int
    {
        $album = $this->loadAccessibleAlbum($albumId, $submittedPassword);

        return $this->photoRepository->countByAlbum($album->id);
    }

    public function getPage(int $albumId, int $limit, int $offset, ?string $submittedPassword): AlbumViewResultDTO
    {
        $album = $this->loadAccessibleAlbum($albumId, $submittedPassword);

        $items = $this->photoRepository->getPhotosByAlbum($albumId, $limit, $offset);
        $photoIds = $items->map(static fn (AlbumPhoto $photo): int => $photo->id)->all();

        $eligible = $this->isViewerEligibleToVote();
        $votedPhotoIds = $eligible
            ? array_flip($this->voteRepository->filterVotedPhotoIds($this->currentUser->id, $photoIds))
            : [];

        $photos = [];
        foreach ($items as $photo) {
            $canVote = $eligible
                && $photo->user_id !== $this->currentUser->id
                && ! isset($votedPhotoIds[$photo->id]);
            $photos[] = $this->photoPresenter->present($photo, $canVote);
        }

        $hasAddPhoto = ($album->user_id === $this->currentUser->id && empty($this->currentUser->ban))
            || $this->currentUser->rights >= self::ADMIN_RIGHTS;

        return new AlbumViewResultDTO(
            albumId: $album->id,
            ownerId: $album->user_id,
            albumName: $album->name,
            photos: $photos,
            hasAddPhoto: $hasAddPhoto,
        );
    }

    private function loadAccessibleAlbum(int $albumId, ?string $submittedPassword): Album
    {
        $album = $this->albumRepository->findById($albumId);
        if ($album === null) {
            throw new AlbumNotFoundException();
        }

        $this->ensureAccess->execute($album, $submittedPassword);

        return $album;
    }

    private function isViewerEligibleToVote(): bool
    {
        return empty($this->currentUser->ban)
            && $this->currentUser->postforum > self::VOTE_MIN_POSTS
            && $this->currentUser->datereg < (time() - self::VOTE_MIN_AGE);
    }
}
