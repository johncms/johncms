<?php

declare(strict_types=1);

namespace Johncms\Modules\Collections\Application\Api;

use Johncms\Modules\Collections\Application\UseCases\SaveCollectionSectionUseCase;
use DateTimeImmutable;
use Johncms\Modules\Collections\Application\DTO\CollectionItemFormDTO;
use Johncms\Modules\Collections\Application\DTO\CollectionItemHistoryDTO;
use Johncms\Modules\Collections\Application\DTO\CollectionSectionFormDTO;
use Johncms\Modules\Collections\Application\UseCases\SaveCollectionItemUseCase;
use Johncms\Updates\SystemOperationContext;

final readonly class CollectionsImportApi implements CollectionsImportApiInterface
{
    public function __construct(
        private SystemOperationContext $context,
        private SaveCollectionItemUseCase $save,
        private SaveCollectionSectionUseCase $saveSection,
    ) {
    }

    public function importItem(CollectionItemFormDTO $form, CollectionItemHistoryDTO $history): int
    {
        $this->context->requireActive();
        return $this->save->execute(null, $form, history: $history);
    }

    public function importSection(CollectionSectionFormDTO $form, DateTimeImmutable $createdAt, DateTimeImmutable $updatedAt): int
    {
        $this->context->requireActive();
        return $this->saveSection->execute(null, $form, $createdAt, $updatedAt);
    }
}
