<?php

declare(strict_types=1);

namespace Johncms\Modules\Profile\Application\UseCases;

use Johncms\Modules\Profile\Application\Exceptions\ProfileNotFoundException;
use Johncms\Modules\Profile\Domain\Repository\BanRepositoryInterface;
use Johncms\Modules\Profile\Domain\Repository\KarmaRepositoryInterface;
use Johncms\Modules\Profile\Domain\Repository\ProfileUserRepositoryInterface;
use Johncms\Users\Ban;

final readonly class DeleteBanUseCase
{
    public function __construct(
        private BanRepositoryInterface $banRepository,
        private KarmaRepositoryInterface $karmaRepository,
        private ProfileUserRepositoryInterface $profileUserRepository,
    ) {
    }

    public function getBan(int $targetId, int $banId): Ban
    {
        $ban = $this->banRepository->findForUser($banId, $targetId);
        if ($ban === null) {
            throw new ProfileNotFoundException();
        }

        return $ban;
    }

    public function delete(Ban $ban): void
    {
        // The karma penalty goes with the ban, by what it cost. A ban given while karma was off
        // cost nothing, and the minuses the account has then are the votes of other members.
        $penalty = $this->karmaRepository->findSystemPenalty($ban->user_id, $ban->ban_while);
        if ($penalty !== null) {
            $this->karmaRepository->deleteVote($penalty->id);
            $this->profileUserRepository->subtractKarmaPoints($ban->user_id, false, $penalty->points);
        }

        $this->banRepository->deleteById($ban->id);
    }
}
