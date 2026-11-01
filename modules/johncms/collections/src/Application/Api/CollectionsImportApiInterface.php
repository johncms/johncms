<?php

declare(strict_types=1);

namespace Johncms\Modules\Collections\Application\Api;

use DateTimeImmutable;
use Johncms\Modules\Collections\Application\DTO\CollectionItemFormDTO;
use Johncms\Modules\Collections\Application\DTO\CollectionItemHistoryDTO;
use Johncms\Modules\Collections\Application\DTO\CollectionSectionFormDTO;

/** Available only inside the trusted system operation scope opened by update hooks. */
interface CollectionsImportApiInterface
{
    public function importItem(CollectionItemFormDTO $form, CollectionItemHistoryDTO $history): int;

    public function importSection(CollectionSectionFormDTO $form, DateTimeImmutable $createdAt, DateTimeImmutable $updatedAt): int;
}
