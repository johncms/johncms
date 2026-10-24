<?php

declare(strict_types=1);

namespace Johncms\Modules\Profile\Application\UseCases;

use Johncms\Modules\Profile\Domain\Repository\BanRepositoryInterface;

/**
 * Clears the history of an account: every ban that has run out or was lifted goes, each the way
 * deleting it alone goes, its karma penalty with it. A ban still in force stays — ending it is
 * lifting it, which is a step of its own and leaves a record.
 */
final readonly class ClearBanHistoryUseCase
{
    public function __construct(
        private BanRepositoryInterface $banRepository,
        private DeleteBanUseCase $deleteBanUseCase,
    ) {
    }

    public function execute(int $targetId): void
    {
        foreach ($this->banRepository->getFinishedForUser($targetId, time()) as $ban) {
            $this->deleteBanUseCase->delete($ban);
        }
    }
}
