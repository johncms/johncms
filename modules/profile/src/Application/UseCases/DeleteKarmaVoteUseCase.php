<?php

declare(strict_types=1);

namespace Johncms\Modules\Profile\Application\UseCases;

use Johncms\Modules\Profile\Application\Exceptions\ProfileNotFoundException;
use Johncms\Modules\Profile\Domain\Repository\KarmaRepositoryInterface;
use Johncms\Modules\Profile\Domain\Repository\ProfileUserRepositoryInterface;
use Johncms\Users\Karma;

final readonly class DeleteKarmaVoteUseCase
{
    public function __construct(
        private ProfileUserRepositoryInterface $profileUserRepository,
        private KarmaRepositoryInterface $karmaRepository,
    ) {
    }

    public function getVote(int $targetId, int $voteId): Karma
    {
        $vote = $this->karmaRepository->findVote($voteId, $targetId);
        if ($vote === null) {
            throw new ProfileNotFoundException();
        }

        return $vote;
    }

    public function delete(Karma $vote): void
    {
        $this->karmaRepository->deleteVote($vote->id);
        $this->profileUserRepository->subtractKarmaPoints($vote->karma_user, $vote->type === 1, $vote->points);
    }
}
