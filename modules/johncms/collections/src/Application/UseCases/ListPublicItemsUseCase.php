<?php

declare(strict_types=1);

namespace Johncms\Modules\Collections\Application\UseCases;

use DateTimeImmutable;
use DateTimeZone;
use Johncms\Modules\Collections\Application\DTO\PublicItemDTO;
use Johncms\Modules\Collections\Application\Services\ItemContentFormatter;
use Johncms\Modules\Collections\Domain\Models\ContentCollectionItem;
use Johncms\Modules\Collections\Domain\Query\ContentCollectionItemQuery;
use Johncms\Modules\Collections\Domain\Repository\ContentCollectionItemRepositoryInterface;

final readonly class ListPublicItemsUseCase
{
    public function __construct(
        private ContentCollectionItemRepositoryInterface $repository,
        private ItemContentFormatter $contentFormatter,
    ) {
    }

    public function count(int $collectionId, ?int $sectionId, ?DateTimeImmutable $at = null): int
    {
        return $this->repository->countItems($this->query(collectionId: $collectionId, sectionId: $sectionId, at: $at));
    }

    /**
     * @return list<PublicItemDTO>
     */
    public function getPage(int $collectionId, ?int $sectionId, int $limit, int $offset, ?DateTimeImmutable $at = null): array
    {
        return $this->repository->findItems($this->query($collectionId, $sectionId, $limit, $offset, $at))
            ->map(fn (ContentCollectionItem $item): PublicItemDTO => new PublicItemDTO(
                code: $item->code,
                name: $item->name,
                previewText: $this->contentFormatter->format($item->preview_text),
                sectionId: $item->section_id,
            ))
            ->all();
    }

    private function query(int $collectionId, ?int $sectionId, ?int $limit = null, int $offset = 0, ?DateTimeImmutable $at = null): ContentCollectionItemQuery
    {
        // Public listing: only published items (active + publish window).
        return (new ContentCollectionItemQuery(
            collectionId: $collectionId,
            sectionId: $sectionId,
            onlyActive: true,
            limit: $limit ?? 100,
            offset: $offset,
            fields: [],
            at: $at ?? new DateTimeImmutable('now', new DateTimeZone('UTC')),
        ))->forPublication();
    }
}
