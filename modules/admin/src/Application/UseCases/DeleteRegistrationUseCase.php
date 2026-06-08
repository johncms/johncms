<?php

declare(strict_types=1);

namespace Johncms\Modules\Admin\Application\UseCases;

use Johncms\Modules\Admin\Domain\Repository\RegistrationModerationRepositoryInterface;

final readonly class DeleteRegistrationUseCase
{
    public function __construct(
        private RegistrationModerationRepositoryInterface $repository,
    ) {
    }

    public function execute(int $id): void
    {
        $this->repository->delete($id);
    }

    public function executeAll(): void
    {
        $this->repository->deleteAll();
    }

    public function executeByIp(int $ip): void
    {
        $this->repository->deleteByIp($ip);
    }
}
