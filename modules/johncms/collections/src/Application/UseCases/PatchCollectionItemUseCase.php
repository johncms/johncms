<?php

declare(strict_types=1);

namespace Johncms\Modules\Collections\Application\UseCases;

use Johncms\Modules\Collections\Application\DTO\CollectionItemFormDTO;
use Johncms\Modules\Collections\Application\DTO\CollectionItemPatchDTO;
use Johncms\Modules\Collections\Application\Exceptions\CollectionItemFormException;
use Johncms\Modules\Collections\Domain\Repository\CollectionTransactionInterface;
use Johncms\Modules\Collections\Domain\Repository\ContentCollectionFieldRepositoryInterface;
use Johncms\Modules\Collections\Domain\Repository\ContentCollectionItemRepositoryInterface;
use Johncms\Modules\Collections\Domain\Repository\ContentCollectionRepositoryInterface;

final readonly class PatchCollectionItemUseCase
{
    public function __construct(
        private ContentCollectionItemRepositoryInterface $items,
        private ContentCollectionRepositoryInterface $collections,
        private ContentCollectionFieldRepositoryInterface $fields,
        private CollectionTransactionInterface $transaction,
        private SaveCollectionItemUseCase $save,
    ) {
    }

    public function execute(int $id, CollectionItemPatchDTO $patch, ?int $actorId = null): void
    {
        $item = $this->items->findById($id) ?? throw new CollectionItemFormException(['id' => [d__('collections', 'Item not found.')]]);
        $this->transaction->run(function () use ($id, $item, $patch, $actorId): void {
            $this->collections->findForUpdate($item->collection_id);
            $current = $this->items->findWithValuesForUpdate($id)
                ?? throw new CollectionItemFormException(['id' => [d__('collections', 'Item not found.')]]);
            $attributes = [
                'collectionId' => $current->collection_id, 'sectionId' => $current->section_id,
                'code' => $current->code, 'name' => $current->name, 'active' => $current->active,
                'activeFrom' => $current->getRawOriginal('active_from'), 'activeTo' => $current->getRawOriginal('active_to'),
                'sort' => $current->sort, 'previewText' => $current->preview_text, 'detailText' => $current->detail_text,
            ];
            foreach ($patch->attributes as $key => $value) {
                $valid = match ($key) {
                    'name', 'code' => is_string($value),
                    'active' => is_bool($value),
                    'sort' => is_int($value) || is_string($value),
                    'sectionId' => $value === null || is_int($value),
                    'previewText', 'detailText', 'activeFrom', 'activeTo', 'uploadContext' => $value === null || is_string($value),
                    default => false,
                };
                if (! $valid) {
                    throw new CollectionItemFormException([$key => [d__('collections', 'Unknown patch property or invalid value type.')]]);
                }
                $attributes[$key] = $value;
            }
            $values = [];
            foreach ($current->getValuesMap() as $code => $value) {
                $values[$code] = is_array($value) ? array_map($this->stringValue(...), $value) : $this->stringValue($value);
            }
            $definitions = $this->fields->getByCollection($current->collection_id)->keyBy('code');
            foreach ($patch->values as $code => $value) {
                $values[$code] = $value ?? (($definitions->get($code)->multiple ?? false) ? [] : '');
            }
            $attributes['values'] = $values;
            $this->save->execute(
                $id,
                new CollectionItemFormDTO(...$attributes),
                $actorId,
                valueFields: array_keys($patch->values),
                clearFields: array_keys(array_filter($patch->values, static fn ($value): bool => $value === null))
            );
        });
    }

    private function stringValue(mixed $value): string
    {
        return is_bool($value) ? ($value ? '1' : '0') : (string) ($value ?? '');
    }
}
