<?php

declare(strict_types=1);

namespace Johncms\Modules\Album\Application\UseCases;

use Johncms\Auth\Authorization\AccessCheckerInterface;
use Johncms\Modules\Album\Application\DTO\PhotoViewDTO;
use Johncms\Modules\Album\Application\Services\AlbumPermissions;
use Johncms\Modules\Album\Application\Services\PhotoPresenter;
use Johncms\Modules\Album\Domain\Enums\TopFilter;
use Johncms\Modules\Album\Domain\Models\AlbumPhoto;
use Johncms\Modules\Album\Domain\Repository\AlbumPhotoRepositoryInterface;
use Johncms\Modules\Album\Domain\Repository\AlbumVoteRepositoryInterface;
use Johncms\Users\User;

final readonly class GetTopUseCase
{
    private const VOTE_MIN_POSTS = 5;
    private const VOTE_MIN_AGE = 259200; // 3 days since registration

    public function __construct(
        private AccessCheckerInterface $accessChecker,
        private AlbumPhotoRepositoryInterface $albumPhotoRepository,
        private AlbumVoteRepositoryInterface $albumVoteRepository,
        private PhotoPresenter $photoPresenter,
        private User $currentUser,
    ) {
    }

    public function count(TopFilter $filter): int
    {
        return $this->albumPhotoRepository->countTop($filter, $this->restrictForUser(), $this->currentUser->id);
    }

    /**
     * @return list<PhotoViewDTO>
     */
    public function getPage(TopFilter $filter, int $limit, int $offset): array
    {
        $photos = $this->albumPhotoRepository->getTop(
            $filter,
            $this->restrictForUser(),
            $this->currentUser->id,
            $limit,
            $offset
        );

        $photoIds = $photos->map(static fn (AlbumPhoto $photo): int => $photo->id)->all();

        $userEligibleToVote = $this->isUserEligibleToVote();
        $votedPhotoIds = $userEligibleToVote
            ? array_flip($this->albumVoteRepository->filterVotedPhotoIds($this->currentUser->id, $photoIds))
            : [];

        $result = [];
        foreach ($photos as $photo) {
            $canVote = $userEligibleToVote
                && $photo->user_id !== $this->currentUser->id
                && ! isset($votedPhotoIds[$photo->id]);
            $result[] = $this->photoPresenter->present($photo, $canVote);
        }

        return $result;
    }

    /**
     * Moderators see every photo; regular users only see public photos and their own.
     */
    private function restrictForUser(): ?int
    {
        return $this->accessChecker->allows(AlbumPermissions::MODERATE)
            ? null
            : $this->currentUser->id;
    }

    private function isUserEligibleToVote(): bool
    {
        return empty($this->currentUser->ban)
            && $this->currentUser->postforum > self::VOTE_MIN_POSTS
            && $this->currentUser->datereg < (time() - self::VOTE_MIN_AGE);
    }
}
