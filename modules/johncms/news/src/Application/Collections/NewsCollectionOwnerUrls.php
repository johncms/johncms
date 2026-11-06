<?php

declare(strict_types=1);

namespace Johncms\Modules\News\Application\Collections;

use Johncms\Modules\Collections\Application\Api\CollectionSectionsApiInterface;
use Johncms\Modules\Collections\Application\DTO\CollectionItemDTO;
use Johncms\Modules\Collections\Application\DTO\CollectionRecordDTO;
use Johncms\Modules\Collections\Application\DTO\CollectionSectionDTO;
use Johncms\Modules\Collections\Application\Extension\CollectionOwnerUrlProviderInterface;

final readonly class NewsCollectionOwnerUrls implements CollectionOwnerUrlProviderInterface
{
    public function __construct(private CollectionSectionsApiInterface $sections)
    {
    }

    public function owner(): string
    {
        return NewsCollectionDeclaration::OWNER;
    }

    public function collectionUrl(CollectionRecordDTO $collection): ?string
    {
        return $this->owns($collection) ? '/news/' : null;
    }

    public function itemUrl(CollectionRecordDTO $collection, CollectionItemDTO $item): ?string
    {
        return $this->itemUrls($collection, [$item])[$item->id] ?? null;
    }

    public function itemUrls(CollectionRecordDTO $collection, array $items): array
    {
        if (! $this->owns($collection)) {
            return [];
        }
        $paths = $this->sectionPaths($collection->id, array_map(static fn (CollectionItemDTO $item): ?int => $item->sectionId, $items));
        $urls = [];
        foreach ($items as $item) {
            $path = $item->sectionId !== null ? ($paths[$item->sectionId] ?? null) : '';
            if ($path !== null && $item->collectionId === $collection->id) {
                $urls[$item->id] = '/news/' . ($path !== '' ? $path . '/' : '') . $item->code . '.html';
            }
        }
        return $urls;
    }

    public function sectionUrl(CollectionRecordDTO $collection, CollectionSectionDTO $section): ?string
    {
        return $this->sectionUrls($collection, [$section])[$section->id] ?? null;
    }

    /** @param list<CollectionSectionDTO> $sections
     * @return array<int, string> Keyed by section id; sections without an address are left out.
     */
    public function sectionUrls(CollectionRecordDTO $collection, array $sections): array
    {
        if (! $this->owns($collection)) {
            return [];
        }
        $paths = $this->sectionPaths($collection->id, array_map(static fn (CollectionSectionDTO $section): int => $section->id, $sections));
        $urls = [];
        foreach ($sections as $section) {
            if (isset($paths[$section->id]) && $section->collectionId === $collection->id) {
                $urls[$section->id] = '/news/' . $paths[$section->id] . '/';
            }
        }
        return $urls;
    }

    public function previewUrl(CollectionRecordDTO $collection, CollectionItemDTO $item): ?string
    {
        return $this->owns($collection) && $item->collectionId === $collection->id
            ? '/admin/collections/' . $collection->id . '/items/' . $item->id . '/preview' : null;
    }

    public function settingsUrl(CollectionRecordDTO $collection): ?string
    {
        return $this->owns($collection) ? '/admin/news/settings' : null;
    }

    private function owns(CollectionRecordDTO $collection): bool
    {
        return $collection->ownerModule === $this->owner() && $collection->declarationKey === NewsCollectionDeclaration::KEY;
    }

    /** @param list<int|null> $ids
     * @return array<int, string> Code paths keyed by section id.
     */
    private function sectionPaths(int $collectionId, array $ids): array
    {
        $ids = array_values(array_unique(array_filter($ids, static fn (?int $id): bool => $id !== null)));
        if ($ids === []) {
            return [];
        }
        return array_map(
            static fn (array $path): string => implode('/', array_map(static fn (CollectionSectionDTO $section): string => $section->code, $path)),
            $this->sections->getPaths($collectionId, $ids)
        );
    }
}
