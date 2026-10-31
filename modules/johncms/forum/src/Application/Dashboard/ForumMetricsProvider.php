<?php

declare(strict_types=1);

namespace Johncms\Modules\Forum\Application\Dashboard;

use Johncms\Modules\Forum\Domain\Repository\ForumMessageRepositoryInterface;
use Johncms\View\Dashboard\DashboardMetric;
use Johncms\View\Dashboard\DashboardMetricProviderInterface;
use Johncms\View\Dashboard\MetricPeriod;

/**
 * The posts of the forum written each day.
 */
final readonly class ForumMetricsProvider implements DashboardMetricProviderInterface
{
    public function __construct(
        private ForumMessageRepositoryInterface $messages,
    ) {
    }

    public function metrics(MetricPeriod $period): iterable
    {
        yield DashboardMetric::fromTrend(
            d__('forum', 'posts on the forum today'),
            $this->messages->countPostedByDay($period->start, $period->days),
            '/forum',
            30
        );
    }
}
