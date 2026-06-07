<?php

declare(strict_types=1);

namespace Johncms\Modules\Admin\Application\UseCases;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Johncms\Modules\Admin\Domain\Enums\BanListSort;
use Johncms\Modules\Admin\Domain\Repository\BanListRepositoryInterface;

final readonly class GetBanListUseCase
{
    public function __construct(
        private BanListRepositoryInterface $repository,
    ) {
    }

    public function execute(BanListSort $sort, int $page, int $perPage): LengthAwarePaginator
    {
        return $this->repository->paginate($sort, $page, $perPage);
    }
}
