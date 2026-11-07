<?php

declare(strict_types=1);

namespace Johncms\Modules\Collections\Application\Api;

use Johncms\Modules\Collections\Application\DTO\CollectionItemDTO;
use Johncms\Modules\Collections\Application\DTO\CollectionItemPageDTO;
use Johncms\Modules\Collections\Application\DTO\CollectionSectionStatsDTO;
use Johncms\Modules\Collections\Domain\Query\ContentCollectionItemQuery;

/** Visitor-visible content, including content of private collections owned by other modules. */
interface CollectionsPublicApiInterface
{
    /** @return list<CollectionItemDTO> */
    public function getItems(ContentCollectionItemQuery $query): array;

    public function countItems(ContentCollectionItemQuery $query): int;

    public function getPage(ContentCollectionItemQuery $query): CollectionItemPageDTO;

    /**
     * Published items of every section, its subsections included.
     *
     * @return array<int, CollectionSectionStatsDTO> Keyed by section id; a section without items is left out.
     */
    public function getSectionStats(int $collectionId): array;

    /** @return list<string> */
    public function suggestTags(int $collectionId, string $fieldCode, string $prefix = '', int $limit = 10): array;

    public function findItem(int $collectionId, int $id): ?CollectionItemDTO;

    public function findItemByCode(int $collectionId, ?int $sectionId, string $code): ?CollectionItemDTO;

    public function incrementViews(int $id): void;
}
