<?php

declare(strict_types=1);

namespace Tests\Unit\View\Dashboard;

use Johncms\View\Dashboard\DashboardMetric;
use Johncms\View\Dashboard\DashboardMetricProviderInterface;
use Johncms\View\Dashboard\DashboardMetrics;
use Johncms\View\Dashboard\MetricPeriod;
use PHPUnit\Framework\TestCase;

final class DashboardMetricsTest extends TestCase
{
    public function testTheLighterCardsGoFirstAndEqualWeightsKeepTheOrderOfTheModules(): void
    {
        $metrics = new DashboardMetrics([
            $this->provider(new DashboardMetric('Orders', 1), new DashboardMetric('Users', 2, weight: 10)),
            $this->provider(new DashboardMetric('Posts', 3)),
        ]);

        self::assertSame(
            ['Users', 'Orders', 'Posts'],
            array_map(static fn (DashboardMetric $metric): string => $metric->label, $metrics->all(MetricPeriod::endingAt(1_000_000, 7)))
        );
    }

    public function testTheProvidersCountOverThePeriodTheyAreGiven(): void
    {
        $period = MetricPeriod::endingAt(1_000_000, 7);
        $provider = new class implements DashboardMetricProviderInterface {
            public ?MetricPeriod $asked = null;

            public function metrics(MetricPeriod $period): iterable
            {
                $this->asked = $period;

                return [];
            }
        };

        (new DashboardMetrics([$provider]))->all($period);

        self::assertSame($period, $provider->asked);
    }

    public function testTodayIsTheLastDayOfATrendAndTheDayBeforeIsTheOneBeforeIt(): void
    {
        $metric = DashboardMetric::fromTrend('Posts', [4, 7, 2]);

        self::assertSame(2, $metric->value);
        self::assertSame(7, $metric->previous());
    }

    public function testANumberWithoutHistoryHasNoDayBefore(): void
    {
        self::assertNull((new DashboardMetric('Visitors', 5))->previous());
        self::assertSame(0, DashboardMetric::fromTrend('Posts', [])->value);
    }

    public function testThePeriodEndsWithTheMomentItIsCountedAt(): void
    {
        $period = MetricPeriod::endingAt(1_000_000, 3);

        self::assertSame([1_000_000 - 2 * 86400, 1_000_000 - 86400, 1_000_000], $period->dayEnds());
        self::assertSame(1_000_000 - 86400 + 1, $period->todayStart());
        self::assertSame($period->todayStart() - 2 * 86400, $period->start);
    }

    private function provider(DashboardMetric ...$metrics): DashboardMetricProviderInterface
    {
        return new readonly class ($metrics) implements DashboardMetricProviderInterface {
            /**
             * @param list<DashboardMetric> $metrics
             */
            public function __construct(private array $metrics)
            {
            }

            public function metrics(MetricPeriod $period): iterable
            {
                return $this->metrics;
            }
        };
    }
}
