<?php

declare(strict_types=1);

namespace Johncms\Modules\Admin\Application\UseCases;

use Johncms\Modules\Admin\Domain\Repository\RegistrationModerationRepositoryInterface;
use Johncms\Users\User;

/**
 * What the confirmations of removing accounts that wait for approval say: which account, or how
 * many of them would go.
 */
final readonly class GetPendingRegistrationsUseCase
{
    public function __construct(
        private RegistrationModerationRepositoryInterface $repository,
    ) {
    }

    public function find(int $id): ?User
    {
        return $this->repository->findPending($id);
    }

    public function count(): int
    {
        return $this->repository->countPending();
    }

    public function countByIp(int $ip): int
    {
        return $ip > 0 ? $this->repository->countPendingByIp($ip) : 0;
    }
}
