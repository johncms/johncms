<?php

declare(strict_types=1);

namespace Johncms\Modules\Admin\Application\UseCases;

use Johncms\Modules\Admin\Domain\Repository\RegistrationModerationRepositoryInterface;

final readonly class ApproveRegistrationUseCase
{
    public function __construct(
        private RegistrationModerationRepositoryInterface $repository,
    ) {
    }

    public function execute(int $id, string $adminName): void
    {
        $this->repository->approve($id, $adminName);
    }

    public function executeAll(string $adminName): void
    {
        $this->repository->approveAll($adminName);
    }
}
