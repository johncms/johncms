<?php

declare(strict_types=1);

namespace Johncms\Modules\Collections\Application\UseCases;

use Johncms\Modules\Collections\Application\DTO\CollectionFieldListItemDTO;
use Johncms\Modules\Collections\Domain\Models\ContentCollectionField;
use Johncms\Modules\Collections\Domain\Repository\ContentCollectionFieldRepositoryInterface;

final readonly class ListCollectionFieldsUseCase
{
    public function __construct(
        private ContentCollectionFieldRepositoryInterface $repository,
    ) {
    }

    /**
     * @return list<CollectionFieldListItemDTO>
     */
    public function getByCollection(int $collectionId): array
    {
        return $this->repository->getByCollection($collectionId)
            ->map(static fn (ContentCollectionField $field): CollectionFieldListItemDTO => new CollectionFieldListItemDTO(
                id: $field->id,
                code: $field->code,
                name: $field->name,
                type: $field->type,
                required: $field->required,
                multiple: $field->multiple,
                sort: $field->sort,
            ))
            ->all();
    }
}
