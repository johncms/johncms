<?php

declare(strict_types=1);

namespace Johncms\View\CommentFeed;

/**
 * The comments of every switched-on module in one list, the newest first
 * (CommentFeedSourceInterface).
 */
final readonly class CommentFeed
{
    /**
     * @param iterable<CommentFeedSourceInterface> $providers
     */
    public function __construct(private iterable $providers)
    {
    }

    public function count(): int
    {
        $count = 0;

        foreach ($this->providers as $source) {
            $count += $source->countComments();
        }

        return $count;
    }

    /**
     * A page of the merged list. Each source is asked for everything down to the end of the page,
     * since any of them may hold the whole of it; the feed is for what is recent, and the deeper
     * pages that cost more are the ones nobody turns to.
     *
     * @return list<FeedComment>
     */
    public function page(int $limit, int $offset = 0): array
    {
        $comments = [];

        foreach ($this->providers as $source) {
            foreach ($source->latestComments($offset + $limit) as $comment) {
                $comments[] = $comment;
            }
        }

        usort($comments, static fn (FeedComment $left, FeedComment $right): int => $right->time <=> $left->time);

        return array_slice($comments, $offset, $limit);
    }
}
