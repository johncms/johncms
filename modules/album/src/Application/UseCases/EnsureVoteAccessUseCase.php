<?php

declare(strict_types=1);

namespace Johncms\Modules\Album\Application\UseCases;

use Johncms\Modules\Album\Application\Exceptions\VoteNotAllowedException;
use Johncms\Modules\Album\Domain\Models\AlbumPhoto;
use Johncms\Modules\Album\Domain\Repository\AlbumVoteRepositoryInterface;
use Johncms\Users\User;

/**
 * Verifies that the current user is allowed to vote for a photo:
 * the photo is not their own, they are not banned, have enough forum posts,
 * registered long enough ago and have not voted for this photo yet.
 */
final readonly class EnsureVoteAccessUseCase
{
    private const VOTE_MIN_POSTS = 5;
    private const VOTE_MIN_AGE = 259200; // 3 days since registration

    public function __construct(
        private AlbumVoteRepositoryInterface $voteRepository,
        private User $currentUser,
    ) {
    }

    public function execute(AlbumPhoto $photo): void
    {
        $eligible = $photo->user_id !== $this->currentUser->id
            && empty($this->currentUser->ban)
            && $this->currentUser->postforum > self::VOTE_MIN_POSTS
            && $this->currentUser->datereg < (time() - self::VOTE_MIN_AGE)
            && ! $this->voteRepository->hasUserVote($this->currentUser->id, $photo->id);

        if (! $eligible) {
            throw new VoteNotAllowedException();
        }
    }
}
