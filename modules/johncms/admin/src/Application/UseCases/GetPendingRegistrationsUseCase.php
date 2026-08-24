<?php

declare(strict_types=1);

namespace Johncms\Modules\Admin\Application\UseCases;

use Illuminate\Database\Eloquent\Collection;
use Johncms\Modules\Admin\Domain\Repository\RegistrationModerationRepositoryInterface;

final readonly class GetPendingRegistrationsUseCase
{
    public function __construct(
        private RegistrationModerationRepositoryInterface $repository,
    ) {
    }

    public function count(): int
    {
        return $this->repository->countPending();
    }

    /**
     * @return Collection<int, \Johncms\Users\User>
     */
    public function getPage(int $limit, int $offset): Collection
    {
        return $this->repository->getPending($limit, $offset);
    }
}
