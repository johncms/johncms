<?php

declare(strict_types=1);

namespace Johncms\Modules\Profile\Application\UseCases;

use Johncms\Modules\Profile\Domain\Repository\KarmaRepositoryInterface;
use Johncms\Modules\Profile\Domain\Repository\ProfileUserRepositoryInterface;

final readonly class CleanKarmaUseCase
{
    public function __construct(
        private ProfileUserRepositoryInterface $profileUserRepository,
        private KarmaRepositoryInterface $karmaRepository,
    ) {
    }

    public function execute(int $targetId): void
    {
        $this->karmaRepository->deleteAllForTarget($targetId);
        $this->profileUserRepository->resetKarmaTotals($targetId);
    }
}
