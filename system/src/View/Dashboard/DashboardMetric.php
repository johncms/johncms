<?php

declare(strict_types=1);

namespace Johncms\View\Dashboard;

/**
 * A number of the dashboard of the panel: how much of something the site got today, and — when
 * the module can count it by day — how much it got on each day of the period.
 */
final readonly class DashboardMetric
{
    /**
     * @param list<int> $trend  One number per day of the period, oldest first; empty for a number
     *                          that has no history.
     * @param string    $url    Where the card leads; empty for a card that is not a link.
     * @param int       $weight Lighter cards go first; equal weights keep the order of the modules.
     */
    public function __construct(
        public string $label,
        public int $value,
        public array $trend = [],
        public string $url = '',
        public int $weight = 100,
    ) {
    }

    /**
     * A metric counted by day: today is the last of the days.
     *
     * @param list<int> $trend
     */
    public static function fromTrend(string $label, array $trend, string $url = '', int $weight = 100): self
    {
        return new self($label, $trend === [] ? 0 : $trend[array_key_last($trend)], $trend, $url, $weight);
    }

    /**
     * The day before today, or null when the metric has no history.
     */
    public function previous(): ?int
    {
        $count = count($this->trend);

        return $count < 2 ? null : $this->trend[$count - 2];
    }
}
