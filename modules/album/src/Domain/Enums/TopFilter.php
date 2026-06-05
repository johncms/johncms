<?php

declare(strict_types=1);

namespace Johncms\Modules\Album\Domain\Enums;

use Johncms\Exceptions\PageNotFoundException;

/**
 * Photo feeds shown on the album "top" page.
 */
enum TopFilter
{
    case New;
    case RecentComments;
    case Views;
    case Downloads;
    case Comments;
    case Votes;
    case Worst;
    case MyComments;

    /**
     * URL slug. The default feed (New) has no slug — it lives at /album/top.
     */
    public function slug(): ?string
    {
        return match ($this) {
            self::New            => null,
            self::RecentComments => 'recent-comments',
            self::Views          => 'views',
            self::Downloads      => 'downloads',
            self::Comments       => 'comments',
            self::Votes          => 'votes',
            self::Worst          => 'worst',
            self::MyComments     => 'my-comments',
        };
    }

    public function title(): string
    {
        return match ($this) {
            self::New            => __('New photos'),
            self::RecentComments => __('Recent comments'),
            self::Views          => __('Top Views'),
            self::Downloads      => __('Top Downloads'),
            self::Comments       => __('Top Comments'),
            self::Votes          => __('Top Votes'),
            self::Worst          => __('Top Worst'),
            self::MyComments     => __('Unread Comments'),
        };
    }

    /**
     * Whether the feed shows only the current user's own photos (owner-scoped).
     */
    public function isOwnerScoped(): bool
    {
        return $this === self::MyComments;
    }

    public static function fromSlug(?string $slug): self
    {
        return match ($slug) {
            null, ''           => self::New,
            'recent-comments'  => self::RecentComments,
            'views'            => self::Views,
            'downloads'        => self::Downloads,
            'comments'         => self::Comments,
            'votes'            => self::Votes,
            'worst'            => self::Worst,
            'my-comments'      => self::MyComments,
            default            => throw new PageNotFoundException(),
        };
    }
}
