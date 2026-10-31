<?php

declare(strict_types=1);

namespace Johncms\Modules\Admin\Application\DTO;

use Johncms\View\CommentFeed\FeedComment;
use Johncms\View\Dashboard\AttentionItem;
use Johncms\View\Dashboard\DashboardMetric;

final readonly class DashboardContextDTO
{
    /**
     * @param list<AttentionItem>   $attention What the modules ask the administrator to look at, the most pressing first.
     * @param list<DashboardMetric> $metrics   The numbers of the modules, counted over $dayEnds.
     * @param list<int>             $dayEnds   The last second of every day of the trends, oldest first.
     * @param list<FeedComment>     $comments  The latest comments the visitor moderates, the newest first.
     * @param SecurityOverviewDTO   $security  The sign-ins and the bans of the last day.
     * @param SystemOverviewDTO|null $system   The state of the system; null for whoever does not manage the site.
     */
    public function __construct(
        public array $attention,
        public array $metrics,
        public array $dayEnds,
        public array $comments,
        public SecurityOverviewDTO $security,
        public ?SystemOverviewDTO $system,
    ) {
    }
}
