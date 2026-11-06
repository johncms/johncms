<?php

declare(strict_types=1);

namespace Johncms\Modules\Collections\Application\Services;

use Johncms\Modules\Collections\Application\UseCases\EnsureCollectionContentAccessUseCase;
use Johncms\Modules\Collections\Domain\Enums\FieldType;
use Johncms\Modules\Collections\Domain\Enums\FilterOperator;
use Johncms\Modules\Collections\Domain\Query\ContentCollectionItemQuery;
use Johncms\Modules\Collections\Domain\Query\FieldFilterDTO;
use Johncms\Modules\Collections\Domain\Repository\ContentCollectionFieldRepositoryInterface;
use Johncms\Modules\Collections\Domain\Repository\ContentCollectionItemRepositoryInterface;

final readonly class CollectionRelationChoices
{
    public function __construct(
        private ContentCollectionFieldRepositoryInterface $fields,
        private ContentCollectionItemRepositoryInterface $items,
        private EnsureCollectionContentAccessUseCase $access,
    ) {
    }

    /** @param list<string> $selected
     * @return list<array{code: string, label: string}>
     */
    public function search(int $collectionId, string $code, string $term = '', array $selected = []): array
    {
        $this->access->execute($collectionId);
        $field = $this->fields->findByCode($collectionId, $code);
        $target = $field?->settings['target_collection_id'] ?? null;
        if ($field?->type !== FieldType::Relation || ! is_int($target) || $target < 1 || count($selected) > 1000 || mb_strlen($term) > 255) {
            throw new \InvalidArgumentException('Choose a configured relation field and at most 1000 selected values.');
        }
        $this->access->execute($target);
        $items = $this->items->getNameMatches($target, trim($term), 30);
        $ids = array_values(array_filter($selected, static fn (string $value): bool => filter_var($value, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]) !== false));
        if ($ids !== []) {
            $items = $items->merge($this->items->findItems(new ContentCollectionItemQuery(
                collectionId: $target,
                onlyActive: false,
                limit: 1000,
                fields: [],
                includeText: false,
                filters: [new FieldFilterDTO('item.id', FilterOperator::In, array_map(intval(...), $ids))]
            )));
        }
        return $items->map(static fn ($item): array => ['code' => (string) $item->id, 'label' => $item->name])->values()->all();
    }
}
