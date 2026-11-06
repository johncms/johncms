<?php

declare(strict_types=1);

namespace Johncms\Modules\Collections\Application\Services;

use Johncms\Modules\Collections\Application\DTO\CollectionRecordDTO;
use Johncms\Modules\Collections\Application\DTO\CollectionItemDTO;
use Johncms\Modules\Collections\Application\DTO\CollectionSectionDTO;
use Johncms\Modules\Collections\Application\Extension\CollectionOwnerUrlProviderInterface;
use Johncms\Modules\ModuleRegistry;

final readonly class CollectionOwnerUrls
{
    /** @var array<string, CollectionOwnerUrlProviderInterface> */
    private array $providers;

    /** @param iterable<CollectionOwnerUrlProviderInterface> $providers */
    public function __construct(iterable $providers = [], private ?ModuleRegistry $modules = null)
    {
        $byOwner = [];
        foreach ($providers as $provider) {
            if (isset($byOwner[$provider->owner()])) {
                throw new \InvalidArgumentException('Duplicate collection URL owner: ' . $provider->owner());
            }
            $byOwner[$provider->owner()] = $provider;
        }
        $this->providers = $byOwner;
    }

    private function provider(?string $owner): ?CollectionOwnerUrlProviderInterface
    {
        if ($owner === null || ($this->modules !== null && ! isset($this->modules->enabled()[$owner]))) {
            return null;
        }
        return $this->providers[$owner] ?? null;
    }

    public function collection(CollectionRecordDTO $collection): ?string
    {
        return $this->valid($this->provider($collection->ownerModule)?->collectionUrl($collection));
    }

    public function item(CollectionRecordDTO $collection, CollectionItemDTO $item): ?string
    {
        if ($item->collectionId !== $collection->id) {
            throw new \InvalidArgumentException('The URL subject belongs to another collection.');
        }
        return $this->valid($this->provider($collection->ownerModule)?->itemUrl($collection, $item));
    }

    /** @param list<CollectionItemDTO> $items
     * @return array<int, string>
     */
    public function items(CollectionRecordDTO $collection, array $items): array
    {
        foreach ($items as $item) {
            if ($item->collectionId !== $collection->id) {
                throw new \InvalidArgumentException('The URL subject belongs to another collection.');
            }
        }
        $urls = $this->provider($collection->ownerModule)?->itemUrls($collection, $items) ?? [];
        return array_filter(array_map($this->valid(...), $urls), static fn (?string $url): bool => $url !== null);
    }

    public function section(CollectionRecordDTO $collection, CollectionSectionDTO $section): ?string
    {
        if ($section->collectionId !== $collection->id) {
            throw new \InvalidArgumentException('The URL subject belongs to another collection.');
        }
        return $this->valid($this->provider($collection->ownerModule)?->sectionUrl($collection, $section));
    }

    public function preview(CollectionRecordDTO $collection, CollectionItemDTO $item): ?string
    {
        if ($item->collectionId !== $collection->id) {
            throw new \InvalidArgumentException('The URL subject belongs to another collection.');
        }
        return $this->valid($this->provider($collection->ownerModule)?->previewUrl($collection, $item));
    }

    public function settings(CollectionRecordDTO $collection): ?string
    {
        return $this->valid($this->provider($collection->ownerModule)?->settingsUrl($collection));
    }

    private function valid(?string $url): ?string
    {
        if ($url === null || $url === '') {
            return null;
        }
        if (preg_match('/[\x00-\x20\\\\]/', $url)) {
            throw new \InvalidArgumentException('Owner URLs cannot contain controls, spaces or backslashes.');
        }
        if ((str_starts_with($url, '/') && ! str_starts_with($url, '//')) || (filter_var($url, FILTER_VALIDATE_URL) !== false && in_array(parse_url($url, PHP_URL_SCHEME), ['http', 'https'], true))) {
            return $url;
        }
        throw new \InvalidArgumentException('Owner URLs must be site-relative or HTTP(S).');
    }
}
