<?php

declare(strict_types=1);

namespace Johncms\Modules\Collections\Application\Api;

use Johncms\Modules\Collections\Application\DTO\CollectionFieldDTO;
use Johncms\Modules\Collections\Application\DTO\CollectionFieldFormDTO;

/** Schema administration; owner-managed changes additionally require a trusted update scope. */
interface CollectionSchemaApiInterface
{
    /** @return list<CollectionFieldDTO> */
    public function getFields(int $collectionId): array;

    public function saveField(?int $id, CollectionFieldFormDTO $field): int;

    public function deleteField(int $id): void;

    /** Create what the loaded modules declare, for work that cannot wait for the next system:update. */
    public function synchronizeDeclarations(): void;

    public function saveOwnedField(int $collectionId, ?int $id, \Johncms\Modules\Collections\Application\DTO\CollectionFieldDeclarationDTO $field): int;
}
