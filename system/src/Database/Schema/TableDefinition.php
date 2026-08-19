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
 * What a table should look like, written by a migration and read by an adapter.
 *
 * Knows nothing about SQL, about a database or about the library that will carry out the change.
 * That is the point: a migration written against this description outlives the layer underneath
 * it, and replacing that layer is a matter of writing another adapter.
 *
 * The same object serves create() and alter() — the drop and rename methods simply have nothing
 * to describe in a table that does not exist yet.
 */
final class TableDefinition
{
    /** @var list<ColumnDefinition> */
    private array $columns = [];

    /** @var list<IndexDefinition> */
    private array $indexes = [];

    /** @var list<ForeignKeyDefinition> */
    private array $foreignKeys = [];

    /** @var list<string> */
    private array $droppedColumns = [];

    /** @var list<array{from: string, to: string}> */
    private array $renamedColumns = [];

    /** @var list<IndexDefinition> */
    private array $droppedIndexes = [];

    /** @var list<string> */
    private array $droppedForeignKeys = [];

    public function __construct(public readonly string $name)
    {
    }

    // Columns.

    /** Auto-incrementing big integer, primary key. */
    public function id(string $column = 'id'): ColumnDefinition
    {
        return $this->addColumn(ColumnType::Id, $column);
    }

    /** Auto-incrementing unsigned integer, primary key. */
    public function increments(string $column): ColumnDefinition
    {
        return $this->addColumn(ColumnType::Increments, $column);
    }

    /** Auto-incrementing unsigned big integer, primary key. */
    public function bigIncrements(string $column): ColumnDefinition
    {
        return $this->addColumn(ColumnType::BigIncrements, $column);
    }

    public function tinyInteger(string $column): ColumnDefinition
    {
        return $this->addColumn(ColumnType::TinyInteger, $column);
    }

    public function smallInteger(string $column): ColumnDefinition
    {
        return $this->addColumn(ColumnType::SmallInteger, $column);
    }

    public function integer(string $column): ColumnDefinition
    {
        return $this->addColumn(ColumnType::Integer, $column);
    }

    public function bigInteger(string $column): ColumnDefinition
    {
        return $this->addColumn(ColumnType::BigInteger, $column);
    }

    public function boolean(string $column): ColumnDefinition
    {
        return $this->addColumn(ColumnType::Boolean, $column);
    }

    public function double(string $column): ColumnDefinition
    {
        return $this->addColumn(ColumnType::Double, $column);
    }

    public function decimal(string $column, int $total = 8, int $places = 2): ColumnDefinition
    {
        return $this->addColumn(ColumnType::Decimal, $column, total: $total, places: $places);
    }

    public function char(string $column, ?int $length = null): ColumnDefinition
    {
        return $this->addColumn(ColumnType::Char, $column, length: $length);
    }

    /** Without a length the column gets the default one — see SchemaInterface::setDefaultStringLength(). */
    public function string(string $column, ?int $length = null): ColumnDefinition
    {
        return $this->addColumn(ColumnType::String, $column, length: $length);
    }

    public function text(string $column): ColumnDefinition
    {
        return $this->addColumn(ColumnType::Text, $column);
    }

    public function mediumText(string $column): ColumnDefinition
    {
        return $this->addColumn(ColumnType::MediumText, $column);
    }

    public function longText(string $column): ColumnDefinition
    {
        return $this->addColumn(ColumnType::LongText, $column);
    }

    public function json(string $column): ColumnDefinition
    {
        return $this->addColumn(ColumnType::Json, $column);
    }

    public function date(string $column): ColumnDefinition
    {
        return $this->addColumn(ColumnType::Date, $column);
    }

    public function dateTime(string $column): ColumnDefinition
    {
        return $this->addColumn(ColumnType::DateTime, $column);
    }

    public function timestamp(string $column): ColumnDefinition
    {
        return $this->addColumn(ColumnType::Timestamp, $column);
    }

    /**
     * The pair Eloquent maintains by itself. Both are nullable, because a row written by anything
     * other than a model leaves them empty.
     */
    public function timestamps(): void
    {
        $this->timestamp('created_at')->nullable();
        $this->timestamp('updated_at')->nullable();
    }

    /** The column a soft-deleting model marks instead of removing the row. */
    public function softDeletes(string $column = 'deleted_at'): ColumnDefinition
    {
        return $this->timestamp($column)->nullable();
    }

    // Keys.

    /**
     * @param string|list<string> $columns
     */
    public function index(string|array $columns, ?string $name = null): void
    {
        $this->indexes[] = new IndexDefinition(IndexType::Index, $this->columnList($columns), $name);
    }

    /**
     * @param string|list<string> $columns
     */
    public function unique(string|array $columns, ?string $name = null): void
    {
        $this->indexes[] = new IndexDefinition(IndexType::Unique, $this->columnList($columns), $name);
    }

    /**
     * @param string|list<string> $columns
     */
    public function primary(string|array $columns, ?string $name = null): void
    {
        $this->indexes[] = new IndexDefinition(IndexType::Primary, $this->columnList($columns), $name);
    }

    /**
     * @param string|list<string> $columns
     */
    public function foreign(string|array $columns, ?string $name = null): ForeignKeyDefinition
    {
        $foreignKey = new ForeignKeyDefinition($this->columnList($columns), $name);
        $this->foreignKeys[] = $foreignKey;

        return $foreignKey;
    }

    // Removals and renames. Meaningful only inside alter().

    public function dropColumn(string ...$columns): void
    {
        foreach ($columns as $column) {
            $this->droppedColumns[] = $column;
        }
    }

    public function renameColumn(string $from, string $to): void
    {
        $this->renamedColumns[] = ['from' => $from, 'to' => $to];
    }

    public function dropIndex(string $name): void
    {
        $this->droppedIndexes[] = new IndexDefinition(IndexType::Index, name: $name);
    }

    public function dropUnique(string $name): void
    {
        $this->droppedIndexes[] = new IndexDefinition(IndexType::Unique, name: $name);
    }

    public function dropPrimary(?string $name = null): void
    {
        $this->droppedIndexes[] = new IndexDefinition(IndexType::Primary, name: $name);
    }

    public function dropForeign(string $name): void
    {
        $this->droppedForeignKeys[] = $name;
    }

    // What the adapter reads.

    /**
     * @return list<ColumnDefinition>
     */
    public function getColumns(): array
    {
        return $this->columns;
    }

    /**
     * @return list<IndexDefinition>
     */
    public function getIndexes(): array
    {
        return $this->indexes;
    }

    /**
     * @return list<ForeignKeyDefinition>
     */
    public function getForeignKeys(): array
    {
        return $this->foreignKeys;
    }

    /**
     * @return list<string>
     */
    public function getDroppedColumns(): array
    {
        return $this->droppedColumns;
    }

    /**
     * @return list<array{from: string, to: string}>
     */
    public function getRenamedColumns(): array
    {
        return $this->renamedColumns;
    }

    /**
     * @return list<IndexDefinition>
     */
    public function getDroppedIndexes(): array
    {
        return $this->droppedIndexes;
    }

    /**
     * @return list<string>
     */
    public function getDroppedForeignKeys(): array
    {
        return $this->droppedForeignKeys;
    }

    private function addColumn(ColumnType $type, string $name, ?int $length = null, int $total = 8, int $places = 2): ColumnDefinition
    {
        $column = new ColumnDefinition($this, $type, $name, $length, $total, $places);
        $this->columns[] = $column;

        return $column;
    }

    /**
     * @param string|list<string> $columns
     * @return list<string>
     */
    private function columnList(string|array $columns): array
    {
        return is_string($columns) ? [$columns] : array_values($columns);
    }
}
