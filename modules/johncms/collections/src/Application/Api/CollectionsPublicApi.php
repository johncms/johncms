<?php

declare(strict_types=1);

namespace Johncms\Modules\Collections\Application\Api;

use Johncms\Modules\Collections\Application\DTO\CollectionItemDTO;
use DateTimeImmutable;
use DateTimeZone;
use Johncms\Modules\Collections\Application\DTO\CollectionItemPageDTO;
use Johncms\Modules\Collections\Application\DTO\CollectionSectionStatsDTO;
use Johncms\Modules\Collections\Application\Services\CollectionApiMapper;
use Johncms\Modules\Collections\Application\Services\CollectionTagSuggestions;
use Johncms\Modules\Collections\Domain\Query\ContentCollectionItemQuery;
use Johncms\Modules\Collections\Domain\Repository\ContentCollectionItemRepositoryInterface;
use Johncms\Modules\Collections\Domain\Repository\ContentCollectionSectionRepositoryInterface;
use Johncms\Modules\Collections\Domain\Services\SectionTree;

final readonly class CollectionsPublicApi implements CollectionsPublicApiInterface
{
    public function __construct(
        private CollectionsApiInterface $api,
        private ContentCollectionItemRepositoryInterface $items,
        private CollectionApiMapper $mapper,
        private CollectionTagSuggestions $tags,
        private ContentCollectionSectionRepositoryInterface $sections,
    ) {
    }

    public function getItems(ContentCollectionItemQuery $query): array
    {
        return $this->api->getItems($query->forPublication());
    }

    public function countItems(ContentCollectionItemQuery $query): int
    {
        return $this->api->countItems($query->forPublication());
    }

    public function suggestTags(int $collectionId, string $fieldCode, string $prefix = '', int $limit = 10): array
    {
        return $this->tags->suggest(collectionId: $collectionId, fieldCode: $fieldCode, prefix: $prefix, limit: $limit, published: true);
    }

    public function getPage(ContentCollectionItemQuery $query): CollectionItemPageDTO
    {
        return $this->api->getPage($query->forPublication());
    }

    public function getSectionStats(int $collectionId): array
    {
        $utc = new DateTimeZone('UTC');
        $own = $this->items->getPublishedSectionStats($collectionId, new DateTimeImmutable('now', $utc));
        if ($own === []) {
            return [];
        }
        $tree = SectionTree::of($this->sections->getAllByCollection($collectionId));
        $stats = [];
        foreach ($tree->ordered() as $sectionId) {
            $items = 0;
            $last = '';
            foreach ($tree->subtreeOf($sectionId) as $id) {
                if (isset($own[$id])) {
                    $items += $own[$id]['items'];
                    $last = max($last, $own[$id]['last_published_at']);
                }
            }
            if ($items > 0) {
                $stats[$sectionId] = new CollectionSectionStatsDTO(
                    sectionId:       $sectionId,
                    items:           $items,
                    lastPublishedAt: $last !== '' ? new DateTimeImmutable($last, $utc) : null,
                );
            }
        }
        return $stats;
    }

    public function findItem(int $collectionId, int $id): ?CollectionItemDTO
    {
        $item = $this->items->findVisibleById($collectionId, $id);
        return $item === null ? null : $this->mapper->item($item);
    }

    public function findItemByCode(int $collectionId, ?int $sectionId, string $code): ?CollectionItemDTO
    {
        $item = $this->items->findVisibleByCode($collectionId, $sectionId, $code);
        return $item === null ? null : $this->mapper->item($item);
    }

    public function incrementViews(int $id): void
    {
        $this->api->incrementViews($id);
    }
}
