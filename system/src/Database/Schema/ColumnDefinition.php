<?php

/**
 * This file is part of JohnCMS Content Management System.
 *
 * @copyright JohnCMS Community
 * @license   https://opensource.org/licenses/GPL-3.0 GPL-3.0
 * @link      https://johncms.com JohnCMS Project
 */

declare(strict_types=1);

namespace Johncms\Database\Schema;

/**
 * One column of a table, as a migration describes it.
 *
 * A builder, so mutable by nature: the fluent calls after the type are what the author writes.
 * It records the answer and never talks to a database — turning it into DDL is the adapter's job.
 */
final class ColumnDefinition
{
    private bool $unsigned = false;

    private bool $nullable = false;

    private bool $hasDefault = false;

    private mixed $default = null;

    private ?string $comment = null;

    private bool $change = false;

    public function __construct(
        private readonly TableDefinition $table,
        public readonly ColumnType $type,
        public readonly string $name,
        public readonly ?int $length = null,
        public readonly int $total = 8,
        public readonly int $places = 2,
    ) {
    }

    /**
     * Only non-negative values are stored. Where the database has no such notion the adapter is
     * free to ignore it — the column then simply holds a wider range than it is given.
     */
    public function unsigned(): self
    {
        $this->unsigned = true;

        return $this;
    }

    public function nullable(bool $nullable = true): self
    {
        $this->nullable = $nullable;

        return $this;
    }

    public function default(mixed $value): self
    {
        $this->hasDefault = true;
        $this->default = $value;

        return $this;
    }

    public function comment(string $comment): self
    {
        $this->comment = $comment;

        return $this;
    }

    /**
     * Changes an existing column instead of adding one. Meaningful only inside alter().
     *
     * The whole definition is applied, not the difference: a column changed without nullable()
     * becomes NOT NULL even if it used to accept nulls.
     */
    public function change(): self
    {
        $this->change = true;

        return $this;
    }

    public function index(?string $name = null): self
    {
        $this->table->index($this->name, $name);

        return $this;
    }

    public function unique(?string $name = null): self
    {
        $this->table->unique($this->name, $name);

        return $this;
    }

    public function primary(?string $name = null): self
    {
        $this->table->primary($this->name, $name);

        return $this;
    }

    public function isUnsigned(): bool
    {
        return $this->unsigned;
    }

    public function isNullable(): bool
    {
        return $this->nullable;
    }

    public function hasDefault(): bool
    {
        return $this->hasDefault;
    }

    public function getDefault(): mixed
    {
        return $this->default;
    }

    public function getComment(): ?string
    {
        return $this->comment;
    }

    public function isChange(): bool
    {
        return $this->change;
    }
}
