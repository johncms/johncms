<?php

declare(strict_types=1);

namespace Johncms\Modules\Album\Application\Dashboard;

use Johncms\Modules\Album\Domain\Repository\AlbumPhotoRepositoryInterface;
use Johncms\View\Dashboard\DashboardMetric;
use Johncms\View\Dashboard\DashboardMetricProviderInterface;
use Johncms\View\Dashboard\MetricPeriod;

/**
 * The photos uploaded to the albums each day.
 */
final readonly class AlbumMetricsProvider implements DashboardMetricProviderInterface
{
    public function __construct(
        private AlbumPhotoRepositoryInterface $photos,
    ) {
    }

    public function metrics(MetricPeriod $period): iterable
    {
        yield DashboardMetric::fromTrend(
            d__('album', 'photos added today'),
            $this->photos->countAddedByDay($period->start, $period->days),
            '/album',
            70
        );
    }
}
