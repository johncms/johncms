<?php

declare(strict_types=1);

namespace Johncms\Database\Query;

use Illuminate\Database\Eloquent\Builder;

/**
 * Counts the rows of a query by day in a single pass: one sum per day over the rows the period
 * holds, so the index on the column narrows the scan and the database is asked once whatever the
 * number of days. Plain SQL, the same on MySQL, MariaDB and SQLite.
 */
final class DailyCounts
{
    private const int DAY = 86400;

    /**
     * @param Builder<*> $query  The rows to count, already narrowed by whatever else matters.
     * @param string     $column A column holding a Unix timestamp.
     * @param int        $start  The first second of the first day.
     * @return list<int> One number per day, oldest first.
     */
    public static function count(Builder $query, string $column, int $start, int $days): array
    {
        $wrapped = $query->getQuery()->getGrammar()->wrap($column);
        $sums = [];
        $bindings = [];

        for ($day = 0; $day < $days; $day++) {
            $sums[] = sprintf('SUM(CASE WHEN %1$s >= ? AND %1$s < ? THEN 1 ELSE 0 END) AS day_%2$d', $wrapped, $day);
            $bindings[] = $start + $day * self::DAY;
            $bindings[] = $start + ($day + 1) * self::DAY;
        }

        $row = (array) $query
            ->where($column, '>=', $start)
            ->where($column, '<', $start + $days * self::DAY)
            ->toBase()
            ->selectRaw(implode(', ', $sums), $bindings)
            ->first();

        // A sum over no rows is NULL, and MySQL hands a sum over as a decimal string.
        return array_map(static fn (int $day): int => (int) ($row['day_' . $day] ?? 0), range(0, $days - 1));
    }
}
