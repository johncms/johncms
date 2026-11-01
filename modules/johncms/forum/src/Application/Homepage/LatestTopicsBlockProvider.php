<?php

declare(strict_types=1);

namespace Johncms\Modules\Forum\Application\Homepage;

use Johncms\Auth\Authorization\AccessCheckerInterface;
use Johncms\Auth\CurrentUser;
use Johncms\Users\ProfileLinksInterface;
use Johncms\Modules\Forum\Application\Services\ForumSectionPathService;
use Johncms\Modules\Forum\Application\Services\ForumPermissions;
use Johncms\Modules\Forum\Application\Services\ForumTopicPathService;
use Johncms\Modules\Forum\Domain\Repository\ForumTopicRepositoryInterface;
use Johncms\Utils\DateFormatterInterface;
use Johncms\View\Homepage\HomepageBlock;
use Johncms\View\Homepage\HomepageBlockProviderInterface;

final readonly class LatestTopicsBlockProvider implements HomepageBlockProviderInterface
{
    public function __construct(
        private AccessCheckerInterface $accessChecker,
        private ForumTopicRepositoryInterface $topics,
        private ForumTopicPathService $paths,
        private DateFormatterInterface $dates,
        private CurrentUser $currentUser,
        private ProfileLinksInterface $profileLinks,
        private ForumSectionPathService $sections,
    ) {
    }

    public function homepageBlocks(): iterable
    {
        if (! $this->accessChecker->allows(ForumPermissions::VIEW)) {
            return;
        }

        $readerId = $this->currentUser->isValid() ? $this->currentUser->id() : null;
        $items = [];
        foreach ($this->topics->getLatest(5, onlyPublished: true, readerId: $readerId) as $topic) {
            $url = $this->paths->getTopicUrlById((int) $topic['id']);
            // A missing section has no topic page; never advertise a broken link.
            if ($url === null || $url === '/forum/' || (int) $topic['post_count'] < 1) {
                continue;
            }

            $authorId = isset($topic['last_author_id']) ? (int) $topic['last_author_id'] : null;
            $authorName = trim($topic['last_author_display_name'] ?? $topic['last_post_author_name'] ?? '');

            $items[] = [
                'name' => $topic['name'],
                'url' => $url,
                'date' => $this->dates->format((int) $topic['last_post_date']),
                'replies' => max(0, (int) $topic['post_count'] - 1),
                'section' => $topic['rzd_name'],
                'section_url' => $this->sections->getSectionUrlById((int) $topic['section_id']),
                'author_id' => $authorId,
                'author' => $authorName !== '' ? $authorName : d__('forum', 'Guest'),
                'author_url' => $authorId !== null ? $this->profileLinks->urlOf($authorId) : null,
                'unread' => $readerId !== null && (int) ($topic['read_at'] ?? 0) < (int) $topic['last_post_date'],
                'pinned' => (bool) $topic['pinned'],
                'closed' => (bool) $topic['closed'],
                'has_poll' => (bool) $topic['has_poll'],
            ];
        }

        yield new HomepageBlock('@forum/homepage/latest.twig', ['topics' => $items], weight: 20);
    }
}
