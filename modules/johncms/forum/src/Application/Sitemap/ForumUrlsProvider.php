<?php

declare(strict_types=1);

namespace Johncms\Modules\Forum\Application\Sitemap;

use Johncms\Modules\Forum\Application\Services\ForumTopicPathService;
use Johncms\Modules\Forum\Domain\Repository\ForumSectionRepositoryInterface;
use Johncms\Modules\Forum\Domain\Repository\ForumTopicRepositoryInterface;
use Johncms\Sitemap\SitemapUrlEntry;
use Johncms\Sitemap\SitemapUrlProviderInterface;

final class ForumUrlsProvider implements SitemapUrlProviderInterface
{
    public function __construct(
        private readonly ForumSectionRepositoryInterface $sectionRepository,
        private readonly ForumTopicRepositoryInterface $topicRepository,
        private readonly ForumTopicPathService $topicPathService,
    ) {
    }

    public function groupName(): string
    {
        return 'forum';
    }

    /**
     * @return iterable<SitemapUrlEntry>
     */
    public function getEntries(string $homeUrl): iterable
    {
        $sections = $this->sectionRepository->getAllForSitemap();

        $sectionById = [];
        $sectionPathById = [];
        $sectionModelsById = [];

        foreach ($sections as $section) {
            $sectionModelsById[(int) $section->id] = $section;
            $sectionById[(int) $section->id] = [
                'id'     => (int) $section->id,
                'parent' => (int) ($section->parent ?? 0),
                'slug'   => trim((string) $section->slug),
            ];
        }

        foreach (array_keys($sectionById) as $sectionId) {
            $path = $this->resolveSectionPath($sectionId, $sectionById, $sectionPathById);
            if ($path === '') {
                continue;
            }

            yield new SitemapUrlEntry($homeUrl . '/forum/' . $path . '/');
        }

        $topics = $this->topicRepository->getCursorForSitemap();

        foreach ($topics as $topic) {
            $sectionId = (int) ($topic->section_id ?? 0);
            if (! isset($sectionPathById[$sectionId])) {
                $path = $this->resolveSectionPath($sectionId, $sectionById, $sectionPathById);
                if ($path === '') {
                    continue;
                }
            }

            $section = $sectionModelsById[$sectionId] ?? null;
            if ($section === null) {
                continue;
            }

            // Avoid N+1 queries in ForumTopicPathService::getTopicUrl() -> loadMissing('section').
            $topic->setRelation('section', $section);

            $loc = $homeUrl . $this->topicPathService->getTopicUrl($topic);

            $lastmod = $this->formatTimestamp((int) ($topic->last_post_date ?? 0));

            yield new SitemapUrlEntry($loc, $lastmod);
        }
    }

    /**
     * @param array<int, array{id: int, parent: int, slug: string}> $sectionById
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
        if ($section['slug'] === '') {
            return '';
        }

        $path = $section['slug'];
        if ($section['parent'] > 0) {
            $parentPath = $this->resolveSectionPath($section['parent'], $sectionById, $sectionPathById);
            $path = $parentPath !== '' ? $parentPath . '/' . $section['slug'] : $section['slug'];
        }

        $sectionPathById[$sectionId] = $path;

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
