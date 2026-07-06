<?php

declare(strict_types=1);

namespace Johncms\Modules\Collections\Application\UseCases;

use Johncms\Modules\Collections\Domain\Repository\ContentCollectionSectionRepositoryInterface;

final readonly class DeleteCollectionSectionUseCase
{
    public function __construct(
        private ContentCollectionSectionRepositoryInterface $repository,
    ) {
    }

    public function execute(int $id): void
    {
        $this->repository->delete($id);
    }
}
