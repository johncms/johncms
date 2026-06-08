<?php

declare(strict_types=1);

namespace Johncms\Modules\Admin\Application\UseCases;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Johncms\Modules\Admin\Domain\Repository\IpBanRepositoryInterface;

final readonly class GetIpBanListUseCase
{
    public function __construct(
        private IpBanRepositoryInterface $repository,
    ) {
    }

    public function execute(int $page, int $perPage): LengthAwarePaginator
    {
        return $this->repository->paginate($page, $perPage);
    }
}
