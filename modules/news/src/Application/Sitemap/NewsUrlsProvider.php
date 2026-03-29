<?php

declare(strict_types=1);

namespace Johncms\Modules\News\Application\Sitemap;

use Johncms\Modules\News\Domain\Models\NewsArticle;
use Johncms\Modules\News\Domain\Models\NewsSection;
use Johncms\Sitemap\SitemapUrlEntry;
use Johncms\Sitemap\SitemapUrlProviderInterface;

final class NewsUrlsProvider implements SitemapUrlProviderInterface
{
    public function groupName(): string
    {
        return 'news';
    }

    /**
     * @return iterable<SitemapUrlEntry>
     */
    public function getEntries(string $homeUrl): iterable
    {
        $sections = NewsSection::query()
            ->select(['id', 'parent', 'code', 'created_at', 'updated_at'])
            ->orderBy('id')
            ->get();

        $sectionPathById = [];
        $sectionById = [];

        foreach ($sections as $section) {
            $sectionById[(int) $section->id] = [
                'id'         => (int) $section->id,
                'parent'     => (int) ($section->parent ?? 0),
                'code'       => trim((string) $section->code),
                'created_at' => (string) ($section->getRawOriginal('created_at') ?? ''),
                'updated_at' => (string) ($section->getRawOriginal('updated_at') ?? ''),
            ];
        }

        foreach (array_keys($sectionById) as $sectionId) {
            $path = $this->resolveSectionPath($sectionId, $sectionById, $sectionPathById);
            if ($path === '') {
                continue;
            }

            $lastmodSource = $sectionById[$sectionId]['updated_at'] !== ''
                ? $sectionById[$sectionId]['updated_at']
                : $sectionById[$sectionId]['created_at'];

            yield new SitemapUrlEntry(
                $homeUrl . '/news/' . $path . '/',
                $this->formatDateTime($lastmodSource),
            );
        }

        $articles = NewsArticle::query()
            ->active()
            ->select(['id', 'section_id', 'code', 'created_at', 'updated_at'])
            ->orderBy('id')
            ->cursor();

        foreach ($articles as $article) {
            $articleCode = trim((string) $article->code);
            if ($articleCode === '') {
                continue;
            }

            $sectionPath = '';
            $sectionId = (int) ($article->section_id ?? 0);
            if ($sectionId > 0) {
                $sectionPath = $sectionPathById[$sectionId]
                    ?? $this->resolveSectionPath($sectionId, $sectionById, $sectionPathById);
            }

            $articlePath = $sectionPath !== ''
                ? '/news/' . $sectionPath . '/' . $articleCode . '.html'
                : '/news/' . $articleCode . '.html';

            $lastmodSource = (string) ($article->getRawOriginal('updated_at') ?? '');
            if ($lastmodSource === '') {
                $lastmodSource = (string) ($article->getRawOriginal('created_at') ?? '');
            }

            yield new SitemapUrlEntry(
                $homeUrl . $articlePath,
                $this->formatDateTime($lastmodSource),
            );
        }
    }

    /**
     * @param array<int, array{id: int, parent: int, code: string, created_at: string, updated_at: string}> $sectionById
     * @param array<int, string> $sectionPathById
     */
    private function resolveSectionPath(int $sectionId, array $sectionById, array &$sectionPathById): string
    {
        if (isset($sectionPathById[$sectionId])) {
            return $sectionPathById[$sectionId];
        }

        if (! isset($sectionById[$sectionId])) {
            return '';
        }

        $section = $sectionById[$sectionId];
        if ($section['code'] === '') {
            return '';
        }

        $path = $section['code'];
        if ($section['parent'] > 0) {
            $parentPath = $this->resolveSectionPath($section['parent'], $sectionById, $sectionPathById);
            $path = $parentPath !== '' ? $parentPath . '/' . $section['code'] : $section['code'];
        }

        $sectionPathById[$sectionId] = $path;

        return $path;
    }

    private function formatDateTime(string $dateTime): ?string
    {
        if ($dateTime === '') {
            return null;
        }

        $timestamp = strtotime($dateTime);
        if ($timestamp === false) {
            return null;
        }

        return gmdate('c', $timestamp);
    }
}
