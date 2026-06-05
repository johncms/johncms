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
        // Roll back the karma penalty that was applied together with the ban
        $points = config('johncms')['karma']['karma_points'] * 2;
        $this->karmaRepository->deleteSystemPenalty($ban->user_id, $ban->ban_while);
        $this->profileUserRepository->subtractKarmaPoints($ban->user_id, false, $points);

        $this->banRepository->deleteById($ban->id);
    }
}
