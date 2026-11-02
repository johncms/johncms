<?php

declare(strict_types=1);

namespace Johncms\Modules\Collections\Application\UseCases;

use Johncms\Modules\Collections\Application\DTO\CollectionItemDTO;
use Johncms\Modules\Collections\Application\Services\CollectionApiMapper;
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
        private CollectionApiMapper $mapper = new CollectionApiMapper(),
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

        return $this->format($this->mapper->item($item));
    }

    public function format(CollectionItemDTO $item): PublicItemDetailDTO
    {
        $valueMap = $item->values;

        $values = [];
        foreach ($this->fieldRepository->getByCollection($item->collectionId) as $field) {
            $raw = $valueMap[$field->code] ?? null;
            $list = $this->normalizeValues($raw);
            if ($list !== []) {
                $values[] = ['label' => $field->name, 'values' => $list];
            }
        }

        return new PublicItemDetailDTO(
            name: $item->name,
            previewText: $this->contentFormatter->format($item->previewText),
            detailText: $this->contentFormatter->format($item->detailText),
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

        return array_values(array_map(static fn ($value): string => $value instanceof \DateTimeInterface ? $value->format('Y-m-d H:i:s') : (string) $value, $list));
    }
}
