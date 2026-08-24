<?php

declare(strict_types=1);

namespace Johncms\Modules\Admin\Application\UseCases;

use Illuminate\Database\Eloquent\Collection;
use Johncms\Modules\Admin\Domain\Enums\BanListSort;
use Johncms\Modules\Admin\Domain\Repository\BanListRepositoryInterface;

final readonly class GetBanListUseCase
{
    public function __construct(
        private BanListRepositoryInterface $repository,
    ) {
    }

    public function count(): int
    {
        return $this->repository->count();
    }

    /**
     * @return Collection<int, \Johncms\Users\User>
     */
    public function getPage(BanListSort $sort, int $limit, int $offset): Collection
    {
        return $this->repository->get($sort, $limit, $offset);
    }
}
