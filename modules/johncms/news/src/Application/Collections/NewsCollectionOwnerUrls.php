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
        if (! $this->owns($collection) || $item->collectionId !== $collection->id) {
            return null;
        }
        $path = $item->sectionId !== null ? $this->sectionPath($collection->id, $item->sectionId) : '';
        return $path === null ? null : '/news/' . ($path !== '' ? $path . '/' : '') . $item->code . '.html';
    }

    public function sectionUrl(CollectionRecordDTO $collection, CollectionSectionDTO $section): ?string
    {
        if (! $this->owns($collection) || $section->collectionId !== $collection->id) {
            return null;
        }
        $path = $this->sectionPath($collection->id, $section->id);
        return $path === null ? null : '/news/' . $path . '/';
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

    private function sectionPath(int $collectionId, int $sectionId): ?string
    {
        $path = $this->sections->getPath($collectionId, $sectionId);
        return $path === [] ? null : implode('/', array_map(static fn (CollectionSectionDTO $section): string => $section->code, $path));
    }
}
