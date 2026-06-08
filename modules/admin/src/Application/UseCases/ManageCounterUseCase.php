<?php

declare(strict_types=1);

namespace Johncms\Modules\Admin\Application\UseCases;

use Johncms\Modules\Admin\Domain\Models\Counter;
use Johncms\Modules\Admin\Domain\Repository\CounterRepositoryInterface;

final readonly class ManageCounterUseCase
{
    public function __construct(
        private CounterRepositoryInterface $repository,
    ) {
    }

    public function find(int $id): ?Counter
    {
        return $this->repository->findById($id);
    }

    public function delete(int $id): void
    {
        $this->repository->delete($id);
    }

    public function toggle(int $id, bool $enabled): void
    {
        $this->repository->setSwitch($id, $enabled);
    }

    public function moveUp(int $id): void
    {
        $this->repository->moveUp($id);
    }

    public function moveDown(int $id): void
    {
        $this->repository->moveDown($id);
    }
}
