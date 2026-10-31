<?php

declare(strict_types=1);

namespace Johncms\Modules\Admin\Application\UseCases;

use Johncms\Modules\Admin\Application\DTO\DashboardContextDTO;
use Johncms\View\CommentFeed\CommentFeed;
use Johncms\View\Dashboard\DashboardAttention;
use Johncms\View\Dashboard\DashboardMetrics;
use Johncms\View\Dashboard\MetricPeriod;

final readonly class GetDashboardContextUseCase
{
    /** Two weeks: enough for the weekly rhythm of a site to show in the trend. */
    private const int TREND_DAYS = 14;

    /** A glance at what was written last; the rest is on the page of the comments. */
    private const int LATEST_COMMENTS = 6;

    public function __construct(
        private DashboardAttention $attention,
        private DashboardMetrics $metrics,
        private CommentFeed $comments,
    ) {
    }

    public function execute(): DashboardContextDTO
    {
        $period = MetricPeriod::endingAt(time(), self::TREND_DAYS);

        return new DashboardContextDTO(
            attention: $this->attention->all(),
            metrics: $this->metrics->all($period),
            dayEnds: $period->dayEnds(),
            comments: $this->comments->page(self::LATEST_COMMENTS),
        );
    }
}
