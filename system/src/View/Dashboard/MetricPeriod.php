<?php

declare(strict_types=1);

namespace Johncms\View\Dashboard;

/**
 * The days the metrics of the dashboard are counted over: rolling periods of 24 hours, the last of
 * them ending now. "Today" on the dashboard is the last of them, as it has always been.
 */
final readonly class MetricPeriod
{
    public const int DAY = 86400;

    /**
     * @param int $start The first second of the first day.
     * @param int $days  How many days, the last one included.
     */
    public function __construct(
        public int $start,
        public int $days,
    ) {
    }

    /**
     * The days up to the given moment, which falls into the last of them.
     */
    public static function endingAt(int $now, int $days): self
    {
        return new self($now + 1 - $days * self::DAY, $days);
    }

    /**
     * The first second of the last day — of "today".
     */
    public function todayStart(): int
    {
        return $this->start + ($this->days - 1) * self::DAY;
    }

    /**
     * @return list<int> The last second of every day, oldest first — what a day is labelled by.
     */
    public function dayEnds(): array
    {
        return array_map(fn (int $day): int => $this->start + ($day + 1) * self::DAY - 1, range(0, $this->days - 1));
    }
}
