<?php

declare(strict_types=1);

namespace Johncms\Modules\Collections\Application\UseCases;

use Johncms\Modules\Collections\Domain\Repository\ContentCollectionItemRepositoryInterface;

final readonly class DeleteCollectionItemUseCase
{
    public function __construct(
        private ContentCollectionItemRepositoryInterface $repository,
    ) {
    }

    public function execute(int $id): void
    {
        $this->repository->delete($id);
    }
}
