<?php

declare(strict_types=1);

namespace Johncms\View\Dashboard;

/**
 * How a module puts its numbers on the dashboard of the panel — posts, uploads, entries of the
 * day — without the dashboard knowing the module exists.
 *
 * A module implements this, the container tags it, and the dashboard shows the cards of the
 * switched-on modules. A module that is switched off has no provider, and its cards are not there.
 */
interface DashboardMetricProviderInterface
{
    /**
     * @return iterable<DashboardMetric> Counted over the given days; nothing when the module has
     *                                   nothing to count.
     */
    public function metrics(MetricPeriod $period): iterable;
}
