<?php

declare(strict_types=1);

namespace Johncms\Modules\Collections\Application\Api;

use Johncms\Modules\Collections\Application\DTO\CollectionItemDTO;
use Johncms\Modules\Collections\Application\DTO\CollectionItemPageDTO;
use Johncms\Modules\Collections\Application\Services\CollectionApiMapper;
use Johncms\Modules\Collections\Domain\Query\ContentCollectionItemQuery;
use Johncms\Modules\Collections\Domain\Repository\ContentCollectionItemRepositoryInterface;

final readonly class CollectionsPublicApi implements CollectionsPublicApiInterface
{
    public function __construct(
        private CollectionsApiInterface $api,
        private ContentCollectionItemRepositoryInterface $items,
        private CollectionApiMapper $mapper,
        private \Johncms\Modules\Collections\Application\Services\CollectionTagSuggestions $tags,
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
        return $this->tags->suggest($collectionId, $fieldCode, $prefix, $limit, published: true);
    }

    public function getPage(ContentCollectionItemQuery $query): CollectionItemPageDTO
    {
        return $this->api->getPage($query->forPublication());
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
