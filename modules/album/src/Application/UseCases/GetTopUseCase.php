<?php

declare(strict_types=1);

namespace Johncms\Modules\Album\Application\UseCases;

use Johncms\Modules\Album\Application\DTO\TopResultDTO;
use Johncms\Modules\Album\Application\Services\PhotoPresenter;
use Johncms\Modules\Album\Domain\Enums\TopFilter;
use Johncms\Modules\Album\Domain\Models\AlbumPhoto;
use Johncms\Modules\Album\Domain\Repository\AlbumPhotoRepositoryInterface;
use Johncms\Modules\Album\Domain\Repository\AlbumVoteRepositoryInterface;
use Johncms\Users\User;

final readonly class GetTopUseCase
{
    private const MODERATOR_RIGHTS = 6;
    private const VOTE_MIN_POSTS = 5;
    private const VOTE_MIN_AGE = 259200; // 3 days since registration

    public function __construct(
        private AlbumPhotoRepositoryInterface $albumPhotoRepository,
        private AlbumVoteRepositoryInterface $albumVoteRepository,
        private PhotoPresenter $photoPresenter,
        private User $currentUser,
    ) {
    }

    public function execute(TopFilter $filter, int $page, int $perPage): TopResultDTO
    {
        // Moderators see every photo; regular users only see public photos and their own.
        $restrictForUser = $this->currentUser->rights >= self::MODERATOR_RIGHTS
            ? null
            : $this->currentUser->id;

        $paginator = $this->albumPhotoRepository->paginateTop(
            $filter,
            $restrictForUser,
            $this->currentUser->id,
            $page,
            $perPage
        );

        $photoIds = array_map(static fn (AlbumPhoto $photo): int => $photo->id, $paginator->items());

        $userEligibleToVote = $this->isUserEligibleToVote();
        $votedPhotoIds = $userEligibleToVote
            ? array_flip($this->albumVoteRepository->filterVotedPhotoIds($this->currentUser->id, $photoIds))
            : [];

        $paginator->getCollection()->transform(function (AlbumPhoto $photo) use ($userEligibleToVote, $votedPhotoIds) {
            $canVote = $userEligibleToVote
                && $photo->user_id !== $this->currentUser->id
                && ! isset($votedPhotoIds[$photo->id]);

            return $this->photoPresenter->present($photo, $canVote);
        });

        return new TopResultDTO($paginator);
    }

    private function isUserEligibleToVote(): bool
    {
        return empty($this->currentUser->ban)
            && $this->currentUser->postforum > self::VOTE_MIN_POSTS
            && $this->currentUser->datereg < (time() - self::VOTE_MIN_AGE);
    }
}
