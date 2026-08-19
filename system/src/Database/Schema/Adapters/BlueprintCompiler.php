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
use Illuminate\Database\Schema\ColumnDefinition as IlluminateColumnDefinition;
use Johncms\Database\Schema\ColumnDefinition;
use Johncms\Database\Schema\ColumnType;
use Johncms\Database\Schema\ForeignKeyDefinition;
use Johncms\Database\Schema\IndexDefinition;
use Johncms\Database\Schema\IndexType;
use Johncms\Database\Schema\SchemaDefinitionException;
use Johncms\Database\Schema\TableDefinition;

/**
 * Restates a table description in the vocabulary of the Eloquent schema builder.
 *
 * Separate from IlluminateSchema so that the translation can be examined without a database:
 * given a blueprint bound to a platform, it answers with the exact DDL a description turns into,
 * which is what pins the column types the CMS has always created.
 */
final readonly class BlueprintCompiler
{
    /**
     * The order is not cosmetic: a column is renamed before it is described, a key is dropped
     * before the column it covers, and a key is added only once its columns exist.
     */
    public function compile(TableDefinition $description, Blueprint $blueprint, bool $fullTextSupported = true): void
    {
        foreach ($description->getRenamedColumns() as $rename) {
            $blueprint->renameColumn($rename['from'], $rename['to']);
        }

        foreach ($description->getColumns() as $column) {
            $this->compileColumn($column, $blueprint);
        }

        foreach ($description->getDroppedForeignKeys() as $name) {
            $blueprint->dropForeign($name);
        }

        foreach ($description->getDroppedIndexes() as $index) {
            $this->compileDroppedIndex($index, $blueprint);
        }

        foreach ($description->getIndexes() as $index) {
            $this->compileIndex($index, $blueprint, $fullTextSupported);
        }

        foreach ($description->getForeignKeys() as $foreignKey) {
            $this->compileForeignKey($foreignKey, $blueprint);
        }

        if ($description->getDroppedColumns() !== []) {
            $blueprint->dropColumn($description->getDroppedColumns());
        }
    }

    private function compileColumn(ColumnDefinition $column, Blueprint $blueprint): void
    {
        $compiled = $this->addColumn($column, $blueprint);

        if ($column->isUnsigned() && ! $column->type->isAutoIncrementing()) {
            $compiled->unsigned();
        }

        if ($column->isNullable()) {
            $compiled->nullable();
        }

        if ($column->hasDefault()) {
            $compiled->default($column->getDefault());
        }

        $comment = $column->getComment();
        if ($comment !== null) {
            $compiled->comment($comment);
        }

        if ($column->isChange()) {
            $compiled->change();
        }
    }

    private function addColumn(ColumnDefinition $column, Blueprint $blueprint): IlluminateColumnDefinition
    {
        return match ($column->type) {
            ColumnType::Id            => $blueprint->id($column->name),
            ColumnType::Increments    => $blueprint->increments($column->name),
            ColumnType::BigIncrements => $blueprint->bigIncrements($column->name),
            ColumnType::TinyInteger   => $blueprint->tinyInteger($column->name),
            ColumnType::SmallInteger  => $blueprint->smallInteger($column->name),
            ColumnType::Integer       => $blueprint->integer($column->name),
            ColumnType::BigInteger    => $blueprint->bigInteger($column->name),
            ColumnType::Boolean       => $blueprint->boolean($column->name),
            ColumnType::Double        => $blueprint->double($column->name),
            ColumnType::Decimal       => $blueprint->decimal($column->name, $column->total, $column->places),
            ColumnType::Char          => $blueprint->char($column->name, $column->length),
            ColumnType::String        => $blueprint->string($column->name, $column->length),
            ColumnType::Text          => $blueprint->text($column->name),
            ColumnType::MediumText    => $blueprint->mediumText($column->name),
            ColumnType::LongText      => $blueprint->longText($column->name),
            ColumnType::Json          => $blueprint->json($column->name),
            ColumnType::Date          => $blueprint->date($column->name),
            ColumnType::DateTime      => $blueprint->dateTime($column->name),
            ColumnType::Timestamp     => $blueprint->timestamp($column->name),
        };
    }

    private function compileIndex(IndexDefinition $index, Blueprint $blueprint, bool $fullTextSupported): void
    {
        if ($index->columns === []) {
            throw new SchemaDefinitionException(sprintf('A %s key covers no columns.', $index->type->value));
        }

        if ($index->type === IndexType::FullText && ! $fullTextSupported) {
            return;
        }

        match ($index->type) {
            IndexType::Index    => $blueprint->index($index->columns, $index->name),
            IndexType::Unique   => $blueprint->unique($index->columns, $index->name),
            IndexType::Primary  => $blueprint->primary($index->columns, $index->name),
            IndexType::FullText => $blueprint->fullText($index->columns, $index->name),
        };
    }

    private function compileDroppedIndex(IndexDefinition $index, Blueprint $blueprint): void
    {
        if ($index->type !== IndexType::Primary && $index->name === null) {
            throw new SchemaDefinitionException(sprintf('A dropped %s key has to be named.', $index->type->value));
        }

        match ($index->type) {
            IndexType::Index    => $blueprint->dropIndex((string) $index->name),
            IndexType::Unique   => $blueprint->dropUnique((string) $index->name),
            IndexType::Primary  => $blueprint->dropPrimary($index->name),
            IndexType::FullText => $blueprint->dropFullText((string) $index->name),
        };
    }

    private function compileForeignKey(ForeignKeyDefinition $foreignKey, Blueprint $blueprint): void
    {
        $compiled = $blueprint->foreign($foreignKey->columns, $foreignKey->name)
            ->references($foreignKey->getReferencedColumns())
            ->on($foreignKey->getReferencedTable());

        $onDelete = $foreignKey->getOnDelete();
        if ($onDelete !== null) {
            $compiled->onDelete($onDelete->value);
        }

        $onUpdate = $foreignKey->getOnUpdate();
        if ($onUpdate !== null) {
            $compiled->onUpdate($onUpdate->value);
        }
    }
}
