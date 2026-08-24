<?php

declare(strict_types=1);

namespace Johncms\Modules\Downloads\Application\Sitemap;

use Johncms\Modules\Downloads\Domain\Models\DownloadCategory;
use Johncms\Modules\Downloads\Domain\Models\DownloadFile;
use Johncms\Sitemap\SitemapUrlEntry;
use Johncms\Sitemap\SitemapUrlProviderInterface;

final class DownloadsUrlsProvider implements SitemapUrlProviderInterface
{
    public function groupName(): string
    {
        return 'downloads';
    }

    /**
     * @return iterable<SitemapUrlEntry>
     */
    public function getEntries(string $homeUrl): iterable
    {
        $categories = DownloadCategory::query()
            ->select(['id', 'refid', 'slug'])
            ->orderBy('id')
            ->get();

        $categoryById = [];
        $categoryPathById = [];

        foreach ($categories as $category) {
            $categoryById[(int) $category->id] = [
                'id'     => (int) $category->id,
                'parent' => (int) ($category->refid ?? 0),
                'slug'   => trim((string) $category->slug),
            ];
        }

        foreach (array_keys($categoryById) as $categoryId) {
            $path = $this->resolveCategoryPath($categoryId, $categoryById, $categoryPathById);
            if ($path === '') {
                continue;
            }

            yield new SitemapUrlEntry($homeUrl . '/downloads/' . $path . '/');
        }

        $files = DownloadFile::query()
            ->where('type', 2)
            ->select(['id', 'refid', 'slug', 'time'])
            ->orderBy('id')
            ->cursor();

        foreach ($files as $file) {
            $slug = trim((string) $file->slug);
            if ($slug === '') {
                $slug = 'file-' . (int) $file->id;
            }

            $categoryId = (int) ($file->refid ?? 0);
            if ($categoryId > 0) {
                $categoryPath = $categoryPathById[$categoryId]
                    ?? $this->resolveCategoryPath($categoryId, $categoryById, $categoryPathById);

                if ($categoryPath === '') {
                    continue;
                }

                $loc = $homeUrl . '/downloads/' . $categoryPath . '/' . $slug . '-' . (int) $file->id . '/';
            } else {
                $loc = $homeUrl . '/downloads/' . $slug . '-' . (int) $file->id . '/';
            }

            yield new SitemapUrlEntry($loc, $this->formatTimestamp((int) ($file->time ?? 0)));
        }
    }

    /**
     * @param array<int, array{id: int, parent: int, slug: string}> $categoryById
     * @param array<int, string> $categoryPathById
     */
    private function resolveCategoryPath(int $categoryId, array $categoryById, array &$categoryPathById): string
    {
        if (isset($categoryPathById[$categoryId])) {
            return $categoryPathById[$categoryId];
        }

        if (! isset($categoryById[$categoryId])) {
            return '';
        }

        $category = $categoryById[$categoryId];
        if ($category['slug'] === '') {
            return '';
        }

        $path = $category['slug'];
        if ($category['parent'] > 0) {
            $parentPath = $this->resolveCategoryPath($category['parent'], $categoryById, $categoryPathById);
            $path = $parentPath !== '' ? $parentPath . '/' . $category['slug'] : $category['slug'];
        }

        $categoryPathById[$categoryId] = $path;

        return $path;
    }

    private function formatTimestamp(int $timestamp): ?string
    {
        if ($timestamp <= 0) {
            return null;
        }

        return gmdate('c', $timestamp);
    }
}
