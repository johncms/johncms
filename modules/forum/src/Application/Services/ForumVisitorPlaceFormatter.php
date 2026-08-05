<?php

declare(strict_types=1);

namespace Johncms\Modules\Forum\Application\Services;

use Johncms\Modules\Forum\Domain\Repository\ForumMessageRepositoryInterface;
use Johncms\Modules\Forum\Domain\Repository\ForumSectionRepositoryInterface;
use Johncms\Modules\Forum\Domain\Repository\ForumTopicRepositoryInterface;
use Johncms\Users\User;
use Twig\Markup;

final readonly class ForumVisitorPlaceFormatter
{
    public function __construct(
        private ForumSectionRepositoryInterface $sectionRepository,
        private ForumSectionPathService $sectionPathService,
        private ForumTopicPathService $topicPathService,
        private ForumTopicRepositoryInterface $topicRepository,
        private ForumMessageRepositoryInterface $messageRepository,
        private User $currentUser,
    ) {
    }

    /**
     * Where a visitor is inside the forum, as a link — markup by contract. Null when there is
     * nothing to show, so that a caller can tell "no place" from an empty string of markup, which
     * an object never is.
     */
    public function format(string $placeUrl): ?Markup
    {
        if ($placeUrl === '') {
            return null;
        }

        $parsedUrl = parse_url($placeUrl);
        $parsedQuery = [];
        $path = rtrim((string) ($parsedUrl['path'] ?? ''), '/');

        if (! empty($parsedUrl['query'])) {
            parse_str($parsedUrl['query'], $parsedQuery);
        }

        $place = 'forum';
        $placeId = 0;
        $actType = '';

        if ($path === '/forum/unread' || $path === '/forum/latest-topics') {
            $place = 'new';
        } elseif ($path === '/forum/files') {
            $place = 'files';
        } elseif ($path === '/forum/search' || str_starts_with($path, '/forum/search/')) {
            $place = 'search';
        } elseif ($path === '/forum/visitors' || preg_match('~^/forum/topic-visitors/\d+$~', $path) === 1) {
            $place = 'who';
        } elseif (preg_match('~^/forum/new-message/(\d+)$~', $path, $matches) === 1) {
            $place = 'say';
            $placeId = (int) $matches[1];
            $actType = 'post';
        } elseif (preg_match('~^/forum/reply-message/(\d+)$~', $path, $matches) === 1) {
            $place = 'say';
            $placeId = (int) $matches[1];
            $actType = 'reply';
        } elseif (preg_match('~^/forum/post/(\d+)$~', $path, $matches) === 1) {
            $place = 'show_post';
            $placeId = (int) $matches[1];
        } elseif (str_starts_with($path, '/forum/')) {
            $topicPathData = $this->topicPathService->parseTopicPath($path);
            if ($topicPathData !== null) {
                $place = 'topic';
                $placeId = $topicPathData['topicId'];
            } else {
                $sectionPath = trim(substr($path, strlen('/forum/')), '/');
                $section = $this->sectionPathService->findSectionByPath($sectionPath);
                if ($section !== null) {
                    $place = $section->section_type === 1 ? 'topics' : 'section';
                    $placeId = $section->id;
                }
            }
        } elseif (! empty($parsedQuery['act'])) {
            $place = (string) $parsedQuery['act'];
            $placeId = (int) ($parsedQuery['id'] ?? 0);
            $actType = (string) ($parsedQuery['type'] ?? '');
        } elseif (! empty($parsedQuery['type'])) {
            $place = (string) $parsedQuery['type'];
            $placeId = (int) ($parsedQuery['id'] ?? 0);
        } elseif (! empty($parsedQuery['id'])) {
            $place = 'section';
            $placeId = (int) $parsedQuery['id'];
        }

        return new Markup(match ($place) {
            'forum' => '<a href="/forum/">' . d__('forum', 'In the forum Main') . '</a>',
            'who' => d__('forum', 'Here, in the List'),
            'files' => '<a href="/forum/files/">' . d__('forum', 'Looking forum files') . '</a>',
            'new' => '<a href="' . ($this->currentUser->isValid() ? '/forum/unread/' : '/forum/latest-topics/') . '">' . d__('forum', 'In the unreads') . '</a>',
            'search' => '<a href="/forum/search/">' . d__('forum', 'Forum search') . '</a>',
            'section' => $this->formatCategoryPlace($placeId),
            'topics' => $this->formatSectionPlace($placeId),
            'say', 'topic' => $this->formatTopicPlace($place, $placeId, $actType),
            'show_post' => $this->formatShowPostPlace($placeId),
            default => '<a href="/forum/">' . d__('forum', 'In the forum Main') . '</a>',
        }, 'UTF-8');
    }

    private function formatCategoryPlace(int $sectionId): string
    {
        $section = $this->sectionRepository->findById($sectionId);
        if ($section === null) {
            return '<a href="/forum/">' . d__('forum', 'In the forum Main') . '</a>';
        }

        return d__('forum', 'In the Category') . ' &quot;<a href="' . $section->url . '">' . $this->escapeName($section->name) . '</a>&quot;';
    }

    private function formatSectionPlace(int $sectionId): string
    {
        $section = $this->sectionRepository->findById($sectionId);
        if ($section === null) {
            return '<a href="/forum/">' . d__('forum', 'In the forum Main') . '</a>';
        }

        return d__('forum', 'In the Section') . ' &quot;<a href="' . $section->url . '">' . $this->escapeName($section->name) . '</a>&quot;';
    }

    private function formatTopicPlace(string $place, int $placeId, string $actType): string
    {
        $topic = null;

        if ($place === 'say' && $actType !== 'post') {
            $message = $this->messageRepository->findById($placeId);
            if ($message !== null && $message->topic !== null) {
                $topic = $message->topic;
            }
        }

        if ($topic === null) {
            $topic = $this->topicRepository->findById($placeId);
        }

        if ($topic === null) {
            return '<a href="/forum/">' . d__('forum', 'In the forum Main') . '</a>';
        }

        $link = '<a href="' . $this->topicPathService->getTopicUrl($topic) . '">' . $this->escapeName($topic->name) . '</a>';

        if ($actType === 'reply') {
            return d__('forum', 'Answers in the Topic') . ' &quot;' . $link . '&quot;';
        }

        if ($place === 'say') {
            return d__('forum', 'Writes in the Topic') . ' &quot;' . $link . '&quot;';
        }

        return d__('forum', 'In the Topic') . ' &quot;' . $link . '&quot;';
    }

    private function formatShowPostPlace(int $messageId): string
    {
        $message = $this->messageRepository->findById($messageId);
        if ($message === null || $message->topic === null) {
            return '<a href="/forum/">' . d__('forum', 'In the forum Main') . '</a>';
        }

        return d__('forum', 'In the Topic') . ' &quot;<a href="' . $this->topicPathService->getTopicUrl($message->topic) . '">' . $this->escapeName($message->topic->name) . '</a>&quot;';
    }

    private function escapeName(?string $name): string
    {
        $name = $name ?? '';

        return $name === '' ? '-----' : htmlentities($name, ENT_QUOTES, 'UTF-8');
    }
}
