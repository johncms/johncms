<?php

declare(strict_types=1);

namespace Johncms\View\Dashboard;

/**
 * The numbers the switched-on modules put on the dashboard of the panel
 * (DashboardMetricProviderInterface).
 */
final readonly class DashboardMetrics
{
    /**
     * @param iterable<DashboardMetricProviderInterface> $providers
     */
    public function __construct(private iterable $providers)
    {
    }

    /**
     * @return list<DashboardMetric> Lighter first; equal weights keep the order of the modules.
     */
    public function all(MetricPeriod $period): array
    {
        $metrics = [];

        foreach ($this->providers as $provider) {
            foreach ($provider->metrics($period) as $metric) {
                $metrics[] = $metric;
            }
        }

        usort($metrics, static fn (DashboardMetric $left, DashboardMetric $right): int => $left->weight <=> $right->weight);

        return $metrics;
    }
}
