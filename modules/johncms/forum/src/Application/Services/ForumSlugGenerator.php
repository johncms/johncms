<?php

declare(strict_types=1);

namespace Johncms\Modules\Forum\Application\Services;

use Illuminate\Support\Str;
use Johncms\Modules\Forum\Domain\Repository\ForumStructureRepositoryInterface;

/**
 * The slug of a forum section: unique among the children of its parent, and never one of the
 * words the routes of the forum have already taken.
 */
final readonly class ForumSlugGenerator
{
    private const RESERVED = [
        'addfile', 'addvote', 'bulk-delete-posts', 'change-topic', 'close', 'delete-post',
        'delete-post-file', 'delete-topic', 'delvote', 'download-file', 'edit-post', 'editvote',
        'files', 'filter', 'latest-topics', 'move-topic', 'new-message', 'new-topic', 'pin-topic',
        'poll-vote', 'poll-voters', 'post', 'reply-message', 'restore-post', 'restore-topic',
        'search', 'topic-visitors', 'topics-period', 'unread', 'visitors',
    ];

    public function __construct(
        private ForumStructureRepositoryInterface $repository,
    ) {
    }

    public function generate(string $name, int $parentId, ?int $excludeId = null): string
    {
        $base = Str::slug($name);
        if ($base === '') {
            $base = 'section';
        }

        if (in_array($base, self::RESERVED, true)) {
            $base .= '-section';
        }

        $slug = $base;
        $suffix = 2;
        while ($this->repository->slugExists($slug, $parentId, $excludeId)) {
            $slug = $base . '-' . $suffix;
            ++$suffix;
        }

        return $slug;
    }
}
