<?php

declare(strict_types=1);

namespace Johncms\Modules\Admin\Application\UseCases;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Johncms\Modules\Admin\Domain\Repository\RegistrationModerationRepositoryInterface;

final readonly class GetPendingRegistrationsUseCase
{
    public function __construct(
        private RegistrationModerationRepositoryInterface $repository,
    ) {
    }

    public function execute(int $page, int $perPage): LengthAwarePaginator
    {
        return $this->repository->paginatePending($page, $perPage);
    }
}
