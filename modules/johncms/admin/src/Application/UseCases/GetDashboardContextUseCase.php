<?php

declare(strict_types=1);

namespace Johncms\Modules\Admin\Application\UseCases;

use Johncms\Modules\Admin\Application\DTO\DashboardContextDTO;
use Johncms\View\Dashboard\DashboardAttention;
use Johncms\View\Dashboard\DashboardMetrics;
use Johncms\View\Dashboard\MetricPeriod;

final readonly class GetDashboardContextUseCase
{
    /** Two weeks: enough for the weekly rhythm of a site to show in the trend. */
    private const int TREND_DAYS = 14;

    public function __construct(
        private DashboardAttention $attention,
        private DashboardMetrics $metrics,
    ) {
    }

    public function execute(): DashboardContextDTO
    {
        $period = MetricPeriod::endingAt(time(), self::TREND_DAYS);

        return new DashboardContextDTO(
            attention: $this->attention->all(),
            metrics: $this->metrics->all($period),
            dayEnds: $period->dayEnds(),
        );
    }
}
