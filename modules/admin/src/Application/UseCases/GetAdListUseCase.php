<?php

declare(strict_types=1);

namespace Johncms\Modules\Admin\Application\UseCases;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Johncms\Modules\Admin\Domain\Repository\AdRepositoryInterface;

final readonly class GetAdListUseCase
{
    public function __construct(
        private AdRepositoryInterface $repository,
    ) {
    }

    public function execute(int $type, int $page, int $perPage): LengthAwarePaginator
    {
        return $this->repository->paginateByType($type, $page, $perPage);
    }
}
