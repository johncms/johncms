<?php

declare(strict_types=1);

namespace Johncms\Modules\Admin\Application\UseCases;

use Johncms\Modules\Admin\Domain\Repository\KarmaRepositoryInterface;

final readonly class ResetKarmaUseCase
{
    public function __construct(
        private KarmaRepositoryInterface $repository,
    ) {
    }

    public function execute(): void
    {
        $this->repository->resetAll();
    }
}
