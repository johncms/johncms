<?php

declare(strict_types=1);

namespace Johncms\Modules\Collections\Application\UseCases;

use Johncms\Modules\Collections\Domain\Repository\ContentCollectionFieldRepositoryInterface;

final readonly class DeleteCollectionFieldUseCase
{
    public function __construct(
        private ContentCollectionFieldRepositoryInterface $repository,
    ) {
    }

    public function execute(int $id): void
    {
        $this->repository->delete($id);
    }
}
