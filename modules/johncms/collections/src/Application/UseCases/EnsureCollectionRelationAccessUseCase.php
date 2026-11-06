<?php

declare(strict_types=1);

namespace Johncms\Modules\Collections\Application\UseCases;

use Johncms\Modules\Collections\Domain\Enums\FieldType;
use Johncms\Modules\Collections\Domain\Repository\ContentCollectionFieldRepositoryInterface;
use Johncms\Modules\Collections\Domain\Repository\ContentCollectionItemRepositoryInterface;

/** User-facing writes check the current visitor; trusted module writes use the structural API. */
final readonly class EnsureCollectionRelationAccessUseCase
{
    public function __construct(
        private ContentCollectionFieldRepositoryInterface $fields,
        private EnsureCollectionContentAccessUseCase $access,
        private ContentCollectionItemRepositoryInterface $items,
    ) {
    }

    /**
     * Only a new reference reaches into the target: one the item already holds reveals nothing
     * and must not stop an editor of the source from saving the rest of the record.
     *
     * @param array<string, mixed> $values
     */
    public function execute(int $collectionId, array $values, bool $applyDefaults = false, ?int $itemId = null): void
    {
        $stored = $itemId !== null ? ($this->items->findWithValues($itemId)?->getValuesMap() ?? []) : [];
        foreach ($this->fields->getForUpdate($collectionId) as $field) {
            if ($field->type !== FieldType::Relation) {
                continue;
            }
            $value = array_key_exists($field->code, $values) ? $values[$field->code] : ($applyDefaults ? ($field->settings['default'] ?? null) : null);
            $entries = array_filter(is_array($value) ? $value : [$value], is_string(...));
            $submitted = array_filter(array_map(trim(...), $entries), static fn (string $entry): bool => $entry !== '');
            $retained = array_map(strval(...), (array) ($stored[$field->code] ?? []));
            if (array_diff($submitted, $retained) !== [] && is_int($field->settings['target_collection_id'] ?? null)) {
                $this->access->execute($field->settings['target_collection_id']);
            }
        }
    }
}
