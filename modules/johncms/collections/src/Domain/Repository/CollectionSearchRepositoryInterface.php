<?php

declare(strict_types=1);

namespace Johncms\Modules\Collections\Domain\Repository;

interface CollectionSearchRepositoryInterface
{
    /** @param array<string, string> $documents */
    public function replace(int $collectionId, int $itemId, array $documents): void;

    public function clearCollection(int $collectionId): void;

    /** @return list<int> */
    public function collectionIds(): array;

    /** @return list<int> */
    public function itemIds(int $collectionId, int $afterId, int $limit): array;

    /** @return list<string> */
    public function suggestTags(int $collectionId, string $fieldCode, string $prefix, int $limit, bool $published): array;
}
