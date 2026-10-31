<?php

declare(strict_types=1);

namespace Johncms\Modules\Downloads\Application\Dashboard;

use Johncms\Modules\Downloads\Domain\Repository\DownloadFileRepositoryInterface;
use Johncms\View\Dashboard\DashboardMetric;
use Johncms\View\Dashboard\DashboardMetricProviderInterface;
use Johncms\View\Dashboard\MetricPeriod;

/**
 * The files uploaded each day that are open to visitors; the ones on moderation wait among the lines that need attention.
 */
final readonly class DownloadsMetricsProvider implements DashboardMetricProviderInterface
{
    public function __construct(
        private DownloadFileRepositoryInterface $files,
    ) {
    }

    public function metrics(MetricPeriod $period): iterable
    {
        yield DashboardMetric::fromTrend(
            d__('downloads', 'files added today'),
            $this->files->countAddedByDay($period->start, $period->days),
            '/downloads',
            50
        );
    }
}
