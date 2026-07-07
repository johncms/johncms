<?php

declare(strict_types=1);

namespace Johncms\Modules\Collections\Application\UseCases;

use Johncms\Modules\Collections\Application\DTO\PublicItemDetailDTO;
use Johncms\Modules\Collections\Application\Services\ItemContentFormatter;
use Johncms\Modules\Collections\Domain\Repository\ContentCollectionFieldRepositoryInterface;
use Johncms\Modules\Collections\Domain\Repository\ContentCollectionItemRepositoryInterface;

final readonly class GetPublicItemUseCase
{
    public function __construct(
        private ContentCollectionItemRepositoryInterface $itemRepository,
        private ContentCollectionFieldRepositoryInterface $fieldRepository,
        private ItemContentFormatter $contentFormatter,
    ) {
    }

    /**
     * Returns the published item detail, or null when it does not exist or is not
     * currently visible.
     */
    public function execute(int $collectionId, ?int $sectionId, string $code): ?PublicItemDetailDTO
    {
        $item = $this->itemRepository->findVisibleByCode($collectionId, $sectionId, $code);
        if ($item === null) {
            return null;
        }

        $valueMap = $item->getValuesMap();

        $values = [];
        foreach ($this->fieldRepository->getByCollection($collectionId) as $field) {
            $raw = $valueMap[$field->code] ?? null;
            $list = $this->normalizeValues($raw);
            if ($list !== []) {
                $values[] = ['label' => $field->name, 'values' => $list];
            }
        }

        return new PublicItemDetailDTO(
            name: $item->name,
            previewText: $item->preview_text,
            detailText: $this->contentFormatter->format($item->detail_text),
            values: $values,
        );
    }

    /**
     * @return list<string>
     */
    private function normalizeValues(mixed $raw): array
    {
        if ($raw === null) {
            return [];
        }

        $list = is_array($raw) ? $raw : [$raw];

        return array_values(array_map(static fn ($value): string => (string) $value, $list));
    }
}
