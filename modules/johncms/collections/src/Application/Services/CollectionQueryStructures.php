<?php

declare(strict_types=1);

namespace Johncms\Modules\Collections\Application\Services;

use Johncms\Modules\Collections\Application\Api\CollectionSchemaApiInterface;
use Johncms\Modules\Collections\Application\Api\CollectionSectionsApiInterface;
use Johncms\Modules\Collections\Application\DTO\CollectionFieldDTO;
use Johncms\Modules\Collections\Domain\Query\CollectionFieldDefinition;
use Johncms\Modules\Collections\Domain\Query\ItemQueryStructure;

/**
 * The structure a selection of items reads, put together from what the schema and the sections
 * already remember, so that every list on a page does not read the fields and the sections again.
 */
final readonly class CollectionQueryStructures
{
    public function __construct(
        private CollectionSchemaApiInterface $schema,
        private CollectionSectionsApiInterface $sections,
        private CollectionApiMapper $mapper,
    ) {
    }

    public function of(int $collectionId): ItemQueryStructure
    {
        $fields = [];
        foreach ($this->schema->getFields($collectionId) as $field) {
            $fields[$field->code] = $this->definition($field);
        }
        return new ItemQueryStructure(
            fields:   $fields,
            sections: $this->mapper->sectionTree($this->sections->getSections($collectionId)),
        );
    }

    private function definition(CollectionFieldDTO $field): CollectionFieldDefinition
    {
        return new CollectionFieldDefinition(
            id:       $field->id,
            code:     $field->code,
            type:     $field->type,
            multiple: $field->multiple,
            role:     $field->role,
        );
    }
}
