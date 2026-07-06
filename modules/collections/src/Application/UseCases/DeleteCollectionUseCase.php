<?php

declare(strict_types=1);

namespace Johncms\Modules\Collections\Application\UseCases;

use Johncms\Modules\Collections\Application\Services\CollectionCodeCacheInterface;
use Johncms\Modules\Collections\Domain\Repository\ContentCollectionRepositoryInterface;

final readonly class DeleteCollectionUseCase
{
    public function __construct(
        private ContentCollectionRepositoryInterface $repository,
        private CollectionCodeCacheInterface $codeCache,
    ) {
    }

    public function execute(int $id): void
    {
        $this->repository->delete($id);
        $this->codeCache->invalidate();
    }
}
