<?php

declare(strict_types=1);

namespace Johncms\Modules\Collections\Application\Sitemap;

use Johncms\Modules\Collections\Domain\Models\ContentCollectionItem;
use Johncms\Modules\Collections\Domain\Repository\ContentCollectionItemRepositoryInterface;
use Johncms\Modules\Collections\Domain\Repository\ContentCollectionRepositoryInterface;
use Johncms\Modules\Collections\Domain\Repository\ContentCollectionSectionRepositoryInterface;
use Johncms\Sitemap\SitemapUrlEntry;
use Johncms\Sitemap\SitemapUrlProviderInterface;

/**
 * Emits sitemap URLs for the public collection pages: each active collection
 * root, each reachable active section, and every published item.
 */
final class CollectionsUrlsProvider implements SitemapUrlProviderInterface
{
    public function __construct(
        private readonly ContentCollectionRepositoryInterface $collectionRepository,
        private readonly ContentCollectionSectionRepositoryInterface $sectionRepository,
        private readonly ContentCollectionItemRepositoryInterface $itemRepository,
    ) {
    }

    public function groupName(): string
    {
        return 'collections';
    }

    /**
     * @return iterable<SitemapUrlEntry>
     */
    public function getEntries(string $homeUrl): iterable
    {
        foreach ($this->collectionRepository->getActiveCodeMap() as $code => $collectionId) {
            $base = $homeUrl . '/' . $code;
            yield new SitemapUrlEntry($base);

            $sectionPaths = $this->buildSectionPaths($collectionId);
            foreach ($sectionPaths as $path) {
                yield new SitemapUrlEntry($base . '/' . $path);
            }

            foreach ($this->itemRepository->getVisibleForSitemap($collectionId) as $item) {
                $sectionId = $item->section_id;
                if ($sectionId !== null && ! isset($sectionPaths[$sectionId])) {
                    // The item sits under an inactive/unreachable section; skip it.
                    continue;
                }

                $prefix = $sectionId !== null ? '/' . $sectionPaths[$sectionId] : '';
                yield new SitemapUrlEntry($base . $prefix . '/' . $item->code . '.html', $this->lastmod($item));
            }
        }
    }

    /**
     * Reachable active-section paths of a collection, keyed by section id.
     *
     * @return array<int, string>
     */
    private function buildSectionPaths(int $collectionId): array
    {
        $nodes = [];
        foreach ($this->sectionRepository->getAllByCollection($collectionId) as $section) {
            if ($section->active) {
                $nodes[$section->id] = ['parent' => $section->parent, 'code' => $section->code];
            }
        }

        $cache = [];
        foreach (array_keys($nodes) as $id) {
            $this->resolvePath($id, $nodes, $cache);
        }

        return array_filter($cache, static fn (?string $path): bool => $path !== null);
    }

    /**
     * @param array<int, array{parent: ?int, code: string}> $nodes
     * @param array<int, ?string> $cache
     */
    private function resolvePath(int $id, array $nodes, array &$cache): ?string
    {
        if (array_key_exists($id, $cache)) {
            return $cache[$id];
        }

        // An ancestor is inactive/missing: the section is unreachable publicly.
        if (! isset($nodes[$id])) {
            return $cache[$id] = null;
        }

        $node = $nodes[$id];
        if ($node['parent'] === null) {
            return $cache[$id] = $node['code'];
        }

        $parentPath = $this->resolvePath($node['parent'], $nodes, $cache);

        return $cache[$id] = $parentPath !== null ? $parentPath . '/' . $node['code'] : null;
    }

    private function lastmod(ContentCollectionItem $item): ?string
    {
        $raw = $item->getRawOriginal('updated_at');
        if (! is_string($raw) || $raw === '') {
            return null;
        }

        $timestamp = strtotime($raw);

        return $timestamp !== false ? gmdate('c', $timestamp) : null;
    }
}
