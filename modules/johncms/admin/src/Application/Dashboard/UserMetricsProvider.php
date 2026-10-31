<?php

declare(strict_types=1);

namespace Johncms\Modules\Admin\Application\Dashboard;

use Johncms\Modules\Admin\Domain\Repository\DashboardRepositoryInterface;
use Johncms\View\Dashboard\DashboardMetric;
use Johncms\View\Dashboard\DashboardMetricProviderInterface;
use Johncms\View\Dashboard\MetricPeriod;

/**
 * The visitors of the day and the accounts registered, first among the numbers of the dashboard.
 * Only the last visit of an account is stored, so the visitors have no history to draw.
 */
final readonly class UserMetricsProvider implements DashboardMetricProviderInterface
{
    public function __construct(
        private DashboardRepositoryInterface $repository,
    ) {
    }

    public function metrics(MetricPeriod $period): iterable
    {
        yield new DashboardMetric(d__('admin', 'registered visitors today'), $this->repository->countActiveUsersSince($period->todayStart()), weight: 10);
        yield DashboardMetric::fromTrend(
            d__('admin', 'users registered today'),
            $this->repository->countRegisteredUsersByDay($period->start, $period->days),
            '/admin/users',
            20
        );
    }
}
