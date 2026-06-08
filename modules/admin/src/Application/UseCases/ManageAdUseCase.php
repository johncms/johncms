<?php

declare(strict_types=1);

namespace Johncms\Modules\Admin\Application\UseCases;

use Johncms\Modules\Admin\Domain\Models\Ad;
use Johncms\Modules\Admin\Domain\Repository\AdRepositoryInterface;

final readonly class ManageAdUseCase
{
    public function __construct(
        private AdRepositoryInterface $repository,
    ) {
    }

    public function find(int $id): ?Ad
    {
        return $this->repository->findById($id);
    }

    public function delete(int $id): void
    {
        $this->repository->delete($id);
    }

    public function deleteInactive(): void
    {
        $this->repository->deleteInactive();
    }

    public function toggle(int $id): void
    {
        $this->repository->toggleActive($id);
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
