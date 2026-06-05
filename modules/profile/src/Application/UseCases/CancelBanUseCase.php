<?php

declare(strict_types=1);

namespace Johncms\Modules\Profile\Application\UseCases;

use Johncms\Modules\Profile\Application\Exceptions\BanException;
use Johncms\Modules\Profile\Application\Exceptions\ProfileNotFoundException;
use Johncms\Modules\Profile\Domain\Repository\BanRepositoryInterface;
use Johncms\Users\Ban;
use Johncms\Users\User;

final readonly class CancelBanUseCase
{
    public function __construct(
        private BanRepositoryInterface $banRepository,
        private User $currentUser,
    ) {
    }

    public function getCancelableBan(int $targetId, int $banId): Ban
    {
        // A ban cannot be cancelled on yourself
        if ($targetId === $this->currentUser->id) {
            throw new ProfileNotFoundException();
        }

        $ban = $this->banRepository->findForUser($banId, $targetId);
        if ($ban === null) {
            throw new ProfileNotFoundException();
        }

        if ($ban->ban_time < time()) {
            throw new BanException(__('Ban not active'));
        }

        return $ban;
    }

    public function cancel(Ban $ban): void
    {
        // Keep the record in the history, just move its end time to now
        $this->banRepository->terminate($ban->id, time());
    }
}
