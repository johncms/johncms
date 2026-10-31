<?php

declare(strict_types=1);

namespace Johncms\Modules\Guestbook\Application\Dashboard;

use Johncms\Modules\Guestbook\Domain\Repository\GuestbookEntryRepositoryInterface;
use Johncms\View\Dashboard\DashboardMetric;
use Johncms\View\Dashboard\DashboardMetricProviderInterface;
use Johncms\View\Dashboard\MetricPeriod;

/**
 * The entries of the public guestbook written each day; the admin club is not counted.
 */
final readonly class GuestbookMetricsProvider implements DashboardMetricProviderInterface
{
    public function __construct(
        private GuestbookEntryRepositoryInterface $entries,
    ) {
    }

    public function metrics(MetricPeriod $period): iterable
    {
        yield DashboardMetric::fromTrend(
            d__('guestbook', 'guestbook entries today'),
            $this->entries->countPostedByDay($period->start, $period->days),
            '/guestbook',
            40
        );
    }
}
