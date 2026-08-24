<?php

declare(strict_types=1);

namespace Johncms\Modules\Admin\Application\UseCases;

use Illuminate\Database\Eloquent\Collection;
use Johncms\Modules\Admin\Domain\Models\Ad;
use Johncms\Modules\Admin\Domain\Repository\AdRepositoryInterface;

final readonly class GetAdListUseCase
{
    public function __construct(
        private AdRepositoryInterface $repository,
    ) {
    }

    public function count(int $type): int
    {
        return $this->repository->countByType($type);
    }

    /**
     * @return Collection<int, Ad>
     */
    public function getPage(int $type, int $limit, int $offset): Collection
    {
        return $this->repository->getByType($type, $limit, $offset);
    }
}
