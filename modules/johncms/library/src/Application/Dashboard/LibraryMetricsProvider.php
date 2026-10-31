<?php

declare(strict_types=1);

namespace Johncms\Modules\Library\Application\Dashboard;

use Johncms\Modules\Library\Domain\Repository\LibraryTextRepositoryInterface;
use Johncms\View\Dashboard\DashboardMetric;
use Johncms\View\Dashboard\DashboardMetricProviderInterface;
use Johncms\View\Dashboard\MetricPeriod;

/**
 * The articles added each day that are published; the ones on moderation wait among the lines that need attention.
 */
final readonly class LibraryMetricsProvider implements DashboardMetricProviderInterface
{
    public function __construct(
        private LibraryTextRepositoryInterface $texts,
    ) {
    }

    public function metrics(MetricPeriod $period): iterable
    {
        yield DashboardMetric::fromTrend(
            d__('library', 'articles added today'),
            $this->texts->countAddedByDay($period->start, $period->days),
            '/library',
            60
        );
    }
}
