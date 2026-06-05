<?php

declare(strict_types=1);

namespace Johncms\Modules\Profile\Application\UseCases;

use Johncms\Modules\Profile\Domain\Repository\BanRepositoryInterface;

final readonly class ClearBanHistoryUseCase
{
    public function __construct(
        private BanRepositoryInterface $banRepository,
    ) {
    }

    public function execute(int $targetId): void
    {
        $this->banRepository->deleteAllForUser($targetId);
    }
}
