<?php

declare(strict_types=1);

namespace Johncms\Modules\Library\Application\Sitemap;

use Johncms\Modules\Library\Domain\Models\LibraryCategory;
use Johncms\Modules\Library\Domain\Models\LibraryText;
use Johncms\Sitemap\SitemapUrlEntry;
use Johncms\Sitemap\SitemapUrlProviderInterface;

final class LibraryUrlsProvider implements SitemapUrlProviderInterface
{
    public function groupName(): string
    {
        return 'library';
    }

    /**
     * @return iterable<SitemapUrlEntry>
     */
    public function getEntries(string $homeUrl): iterable
    {
        $categories = LibraryCategory::query()
            ->select(['id', 'parent', 'slug'])
            ->orderBy('id')
            ->get();

        $categoryById = [];
        $categoryPathById = [];

        foreach ($categories as $category) {
            $categoryById[(int) $category->id] = [
                'id'     => (int) $category->id,
                'parent' => (int) ($category->parent ?? 0),
                'slug'   => trim((string) $category->slug),
            ];
        }

        foreach (array_keys($categoryById) as $categoryId) {
            $path = $this->resolveCategoryPath($categoryId, $categoryById, $categoryPathById);
            if ($path === '') {
                continue;
            }

            yield new SitemapUrlEntry($homeUrl . '/library/' . $path . '/');
        }

        $articles = LibraryText::query()
            ->where('premod', 1)
            ->select(['id', 'cat_id', 'slug', 'time'])
            ->orderBy('id')
            ->cursor();

        foreach ($articles as $article) {
            $categoryId = (int) ($article->cat_id ?? 0);
            $categoryPath = $categoryPathById[$categoryId]
                ?? $this->resolveCategoryPath($categoryId, $categoryById, $categoryPathById);

            if ($categoryPath === '') {
                continue;
            }

            $slug = trim((string) $article->slug);
            if ($slug === '') {
                $slug = 'article-' . (int) $article->id;
            }

            $loc = $homeUrl . '/library/' . $categoryPath . '/' . $slug . '-' . (int) $article->id . '/';

            yield new SitemapUrlEntry($loc, $this->formatTimestamp((int) ($article->time ?? 0)));
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
