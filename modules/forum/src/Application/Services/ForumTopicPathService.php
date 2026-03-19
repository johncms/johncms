<?php

declare(strict_types=1);

namespace Johncms\Modules\Forum\Application\Services;

use Johncms\Modules\Forum\Domain\Models\ForumTopic;
use Johncms\Modules\Forum\Domain\Repository\ForumTopicRepositoryInterface;

final class ForumTopicPathService
{
    public function __construct(
        private ForumSectionPathService $sectionPathService,
        private ForumTopicRepositoryInterface $topicRepository,
    ) {
    }

    public function getTopicSlug(ForumTopic $topic): string
    {
        $slug = trim((string) $topic->slug);

        return $slug !== '' ? $slug : 'topic-' . $topic->id;
    }

    /**
     * @return array{sectionPath: string, topicSlug: string, topicId: int}|null
     */
    public function parseTopicPath(string $path): ?array
    {
        $normalized = trim($path, '/');
        if ($normalized === '' || ! str_starts_with($normalized, 'forum/')) {
            return null;
        }

        $forumPath = trim(substr($normalized, strlen('forum/')), '/');
        $lastSlashPos = strrpos($forumPath, '/');
        if ($lastSlashPos === false) {
            return null;
        }

        $sectionPath = substr($forumPath, 0, $lastSlashPos);
        $topicPart = substr($forumPath, $lastSlashPos + 1);
        if ($sectionPath === '' || preg_match('/^(?<slug>[a-z0-9-]+)-(?<id>\d+)$/', $topicPart, $matches) !== 1) {
            return null;
        }

        return [
            'sectionPath' => $sectionPath,
            'topicSlug'   => $matches['slug'],
            'topicId'     => (int) $matches['id'],
        ];
    }

    public function getTopicUrl(ForumTopic $topic, ?int $page = null): string
    {
        $topic->loadMissing('section');
        if ($topic->section === null) {
            return '/forum/';
        }

        $sectionPath = $this->sectionPathService->getSectionPath($topic->section);
        $url = '/forum/' . $sectionPath . '/' . $this->getTopicSlug($topic) . '-' . $topic->id . '/';

        if ($page !== null && $page > 1) {
            $url .= '?page=' . $page;
        }

        return $url;
    }

    public function getTopicUrlById(int $topicId, ?int $page = null): ?string
    {
        $topic = $this->topicRepository->findById($topicId);
        if ($topic === null) {
            return null;
        }

        return $this->getTopicUrl($topic, $page);
    }
}
