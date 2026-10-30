<?php

declare(strict_types=1);

namespace Johncms\Modules\Admin\Application\UseCases;

use Johncms\Modules\Admin\Domain\Exceptions\AdLimitReachedException;
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

    /**
     * Hides a shown link or shows a hidden one. Returns whether the link is shown now.
     *
     * @throws AdLimitReachedException when a hidden link has no hits or days left
     */
    public function toggle(Ad $ad): bool
    {
        if ($ad->isHidden() && $ad->isExhausted(time())) {
            throw new AdLimitReachedException();
        }

        $this->repository->toggleActive($ad->id);

        return $ad->isHidden();
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
