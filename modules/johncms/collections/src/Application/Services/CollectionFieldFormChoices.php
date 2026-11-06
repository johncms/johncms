<?php

declare(strict_types=1);

namespace Johncms\Modules\Collections\Application\Services;

use InvalidArgumentException;
use Johncms\Modules\Collections\Application\UseCases\EnsureCollectionContentAccessUseCase;
use Johncms\Modules\Collections\Domain\Enums\FieldType;
use Johncms\Modules\Collections\Domain\Enums\FilterOperator;
use Johncms\Modules\Collections\Domain\Query\ContentCollectionItemQuery;
use Johncms\Modules\Collections\Domain\Query\FieldFilterDTO;
use Johncms\Modules\Collections\Domain\Repository\ContentCollectionItemRepositoryInterface;
use Johncms\Modules\Collections\Domain\Repository\ContentCollectionRepositoryInterface;

final readonly class CollectionFieldFormChoices
{
    public function __construct(
        private ContentCollectionRepositoryInterface $collections,
        private ContentCollectionItemRepositoryInterface $items,
        private EnsureCollectionContentAccessUseCase $access,
        private CollectionFileChoices $files,
        private CollectionFieldSettingsValidator $validator,
    ) {
    }

    /** @return list<array{code: string, label: string}> */
    public function collections(): array
    {
        return $this->collections->getAll(max(1, $this->collections->countAll()), 0)
            ->map(static fn ($collection): array => ['code' => (string) $collection->id, 'label' => $collection->name . ' (' . $collection->code . ')'])
            ->values()->all();
    }

    /** @param array<string, mixed> $settings
     * @param list<string> $selected
     * @return list<array{code: string, label: string}>
     */
    public function values(int $collectionId, FieldType $type, array $settings, string $term, array $selected): array
    {
        $this->access->execute($collectionId);
        if (mb_strlen($term) > 255 || count($selected) > 1000) {
            throw new InvalidArgumentException(d__('collections', 'Use a search term of at most 255 characters.'));
        }
        unset($settings['default']);
        $this->validator->validate($type, false, $settings);
        if ($type === FieldType::File && ($settings['file_storage'] ?? 'legacy') === 'registry') {
            return array_map(static fn ($file): array => ['code' => $file->code, 'label' => $file->label . ' (' . $file->sizeLabel . ')'], $this->files->searchSettings($collectionId, 'default', $settings, $term, $selected));
        }
        if ($type !== FieldType::Relation) {
            return [];
        }
        $target = $settings['target_collection_id'];
        $this->access->execute($target);
        if ($this->collections->findById($target) === null) {
            throw new InvalidArgumentException(d__('collections', 'Choose an existing target collection.'));
        }
        $items = $this->items->getNameMatches($target, trim($term), 30);
        $ids = array_values(array_filter($selected, static fn (string $value): bool => filter_var($value, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]) !== false));
        if ($ids !== []) {
            $items = $items->merge($this->items->findItems(new ContentCollectionItemQuery(
                collectionId: $target,
                onlyActive: false,
                limit: 1000,
                fields: [],
                includeText: false,
                filters: [new FieldFilterDTO('item.id', FilterOperator::In, array_map(intval(...), $ids))],
            )));
        }
        return $items->map(static fn ($item): array => ['code' => (string) $item->id, 'label' => $item->name . ' (#' . $item->id . ')'])->values()->all();
    }
}
