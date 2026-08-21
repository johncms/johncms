<?php

declare(strict_types=1);

namespace Tests\Support;

use Illuminate\Database\Capsule\Manager as Capsule;

/**
 * Writes a row a test needs into any table, filling in the columns it did not mention.
 *
 * The tables of 9.x are full of NOT NULL columns without a default — a legacy of MySQL outside
 * strict mode, where a missing value was quietly turned into an empty one. A test names the
 * columns its assertion is about and nothing else; every other required column is filled with the
 * empty value of its type, which is what the site would have stored anyway.
 */
final class Fixture
{
    /**
     * @param array<string, mixed> $attributes
     * @return int The id of the row.
     */
    public static function insert(string $table, array $attributes = []): int
    {
        return (int) Capsule::table($table)->insertGetId(self::withRequiredColumns($table, $attributes));
    }

    /**
     * @param array<string, mixed> $attributes
     * @return array<string, mixed>
     */
    public static function withRequiredColumns(string $table, array $attributes): array
    {
        foreach (Capsule::connection()->getSchemaBuilder()->getColumns($table) as $column) {
            $name = (string) $column['name'];

            if (
                $column['nullable']
                || $column['auto_increment']
                || $column['default'] !== null
                || array_key_exists($name, $attributes)
            ) {
                continue;
            }

            $type = (string) $column['type'];
            $attributes[$name] = str_contains($type, 'char') || str_contains($type, 'text') ? '' : 0;
        }

        return $attributes;
    }
}
