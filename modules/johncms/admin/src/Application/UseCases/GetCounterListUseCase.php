<?php

declare(strict_types=1);

namespace Johncms\Modules\Admin\Application\UseCases;

use Illuminate\Support\Collection;
use Johncms\Modules\Admin\Domain\Repository\CounterRepositoryInterface;

final readonly class GetCounterListUseCase
{
    public function __construct(
        private CounterRepositoryInterface $repository,
    ) {
    }

    /**
     * @return Collection<int, \Johncms\Modules\Admin\Domain\Models\Counter>
     */
    public function execute(): Collection
    {
        return $this->repository->all();
    }
}
