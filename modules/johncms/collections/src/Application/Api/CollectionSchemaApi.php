<?php

declare(strict_types=1);

namespace Johncms\Modules\Collections\Application\Api;

use Johncms\Modules\Collections\Application\DTO\CollectionFieldDTO;
use Johncms\Modules\Collections\Application\DTO\CollectionFieldDeclarationDTO;
use Johncms\Modules\Collections\Application\DTO\CollectionFieldFormDTO;
use Johncms\Modules\Collections\Application\Services\CollectionDeclarationsSynchronizer;
use Johncms\Modules\Collections\Application\UseCases\DeleteCollectionFieldUseCase;
use Johncms\Modules\Collections\Application\UseCases\SaveCollectionFieldUseCase;
use Johncms\Modules\Collections\Domain\Repository\ContentCollectionFieldRepositoryInterface;

final readonly class CollectionSchemaApi implements CollectionSchemaApiInterface
{
    public function __construct(
        private ContentCollectionFieldRepositoryInterface $fields,
        private SaveCollectionFieldUseCase $save,
        private DeleteCollectionFieldUseCase $delete,
        private CollectionDeclarationsSynchronizer $declarations,
    ) {
    }

    public function getFields(int $collectionId): array
    {
        return $this->fields->getByCollection($collectionId)->map(static fn ($field): CollectionFieldDTO => new CollectionFieldDTO(
            $field->id,
            $field->collection_id,
            $field->code,
            $field->name,
            $field->type,
            $field->required,
            $field->multiple,
            $field->in_list,
            $field->sort,
            $field->role,
            $field->settings ?? [],
            $field->owner_module,
            $field->getRawOriginal('name'),
            $field->label_domain
        ))->all();
    }

    public function saveField(?int $id, CollectionFieldFormDTO $field): int
    {
        return $this->save->execute($id, $field);
    }

    public function saveOwnedField(int $collectionId, ?int $id, CollectionFieldDeclarationDTO $field): int
    {
        return $this->save->execute($id, new CollectionFieldFormDTO(
            $collectionId,
            $field->code,
            $field->label->message,
            $field->type,
            $field->required,
            $field->multiple,
            $field->sort,
            $field->inList,
            $field->role,
            $field->settings
        ), $field);
    }

    public function deleteField(int $id): void
    {
        $this->delete->execute($id);
    }

    public function synchronizeDeclarations(): void
    {
        $this->declarations->synchronize();
    }
}
