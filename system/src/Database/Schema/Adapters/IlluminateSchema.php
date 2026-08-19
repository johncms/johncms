<?php

/**
 * This file is part of JohnCMS Content Management System.
 *
 * @copyright JohnCMS Community
 * @license   https://opensource.org/licenses/GPL-3.0 GPL-3.0
 * @link      https://johncms.com JohnCMS Project
 */

declare(strict_types=1);

namespace Johncms\Database\Schema\Adapters;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Schema\Builder;
use Johncms\Database\Schema\SchemaInterface;
use Johncms\Database\Schema\TableDefinition;

/**
 * Carries out a table description with the schema builder of Eloquent.
 *
 * With BlueprintCompiler, the only place in the migration machinery that knows
 * illuminate/database exists. It is here rather than in the migrations for two reasons: the
 * tables of every existing installation were created by exactly this builder, so going through it
 * keeps a fresh site identical to an upgraded one; and when Eloquent leaves, only these two
 * classes are rewritten.
 */
final readonly class IlluminateSchema implements SchemaInterface
{
    /** The databases whose schema builder can write a word index. */
    private const array DRIVERS_WITH_FULL_TEXT = ['mysql', 'mariadb'];

    public function __construct(
        private Builder $builder,
        private BlueprintCompiler $compiler = new BlueprintCompiler(),
    ) {
    }

    public function create(string $table, callable $definition): void
    {
        $description = $this->describe($table, $definition);

        $this->builder->create($table, function (Blueprint $blueprint) use ($description): void {
            $this->compiler->compile($description, $blueprint, $this->supportsFullText());
        });
    }

    public function alter(string $table, callable $definition): void
    {
        $description = $this->describe($table, $definition);

        $this->builder->table($table, function (Blueprint $blueprint) use ($description): void {
            $this->compiler->compile($description, $blueprint, $this->supportsFullText());
        });
    }

    public function drop(string $table): void
    {
        $this->builder->drop($table);
    }

    public function dropIfExists(string $table): void
    {
        $this->builder->dropIfExists($table);
    }

    public function rename(string $from, string $to): void
    {
        $this->builder->rename($from, $to);
    }

    public function hasTable(string $table): bool
    {
        return $this->builder->hasTable($table);
    }

    public function hasColumn(string $table, string $column): bool
    {
        return $this->builder->hasColumn($table, $column);
    }

    public function hasIndex(string $table, string $index): bool
    {
        return $this->builder->hasIndex($table, $index);
    }

    public function hasForeignKey(string $table, string $name): bool
    {
        if (! $this->builder->hasTable($table)) {
            return false;
        }

        foreach ($this->builder->getForeignKeys($table) as $foreignKey) {
            if ($foreignKey['name'] === $name) {
                return true;
            }
        }

        return false;
    }

    public function getColumnType(string $table, string $column): ?string
    {
        if (! $this->builder->hasTable($table)) {
            return null;
        }

        foreach ($this->builder->getColumns($table) as $existing) {
            if ($existing['name'] === $column) {
                return (string) $existing['type'];
            }
        }

        return null;
    }

    public function setDefaultStringLength(int $length): void
    {
        Builder::defaultStringLength($length);
    }

    private function supportsFullText(): bool
    {
        return in_array($this->builder->getConnection()->getDriverName(), self::DRIVERS_WITH_FULL_TEXT, true);
    }

    /**
     * @param callable(TableDefinition): void $definition
     */
    private function describe(string $table, callable $definition): TableDefinition
    {
        $description = new TableDefinition($table);
        $definition($description);

        return $description;
    }
}
