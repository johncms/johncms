<?php

declare(strict_types=1);

namespace Johncms\Modules\Collections\Application\Sitemap;

use DateTimeImmutable;
use DateTimeZone;
use Johncms\Modules\Collections\Domain\Models\ContentCollectionItem;
use Johncms\Modules\Collections\Domain\Repository\ContentCollectionItemRepositoryInterface;
use Johncms\Modules\Collections\Domain\Repository\ContentCollectionRepositoryInterface;
use Johncms\Modules\Collections\Domain\Repository\ContentCollectionSectionRepositoryInterface;
use Johncms\Modules\Collections\Domain\Services\SectionTree;
use Johncms\Sitemap\SitemapUrlEntry;
use Johncms\Sitemap\SitemapUrlProviderInterface;

/**
 * Emits sitemap URLs for the public collection pages: each active & public
 * collection root, each reachable active section, and every published item.
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
        $at = new DateTimeImmutable('now', new DateTimeZone('UTC'));
        foreach ($this->collectionRepository->getPublicCodeMap() as $code => $collectionId) {
            $base = $homeUrl . '/' . $code;
            yield new SitemapUrlEntry($base);

            $sectionPaths = $this->buildSectionPaths($collectionId);
            foreach ($sectionPaths as $path) {
                yield new SitemapUrlEntry($base . '/' . $path);
            }

            foreach ($this->itemRepository->getVisibleForSitemap($collectionId, $at) as $item) {
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
        $tree = SectionTree::of($this->sectionRepository->getAllByCollection($collectionId));
        $paths = [];
        foreach ($tree->visibleIds() as $id) {
            $paths[$id] = implode('/', $tree->codePathOf($id));
        }
        return $paths;
    }

    private function lastmod(ContentCollectionItem $item): ?string
    {
        $raw = $item->getRawOriginal('updated_at');
        if (! is_string($raw) || $raw === '') {
            return null;
        }

        return (new DateTimeImmutable($raw, new DateTimeZone('UTC')))->format(DATE_ATOM);
    }
}
