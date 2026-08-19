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
 * A foreign key, written the way it reads: foreign('user_id')->references('id')->on('users').
 */
final class ForeignKeyDefinition
{
    /** @var list<string> */
    private array $referencedColumns = ['id'];

    private ?string $referencedTable = null;

    private ?ReferentialAction $onDelete = null;

    private ?ReferentialAction $onUpdate = null;

    /**
     * @param list<string> $columns
     */
    public function __construct(
        public readonly array $columns,
        public readonly ?string $name = null,
    ) {
    }

    public function references(string ...$columns): self
    {
        $this->referencedColumns = array_values($columns);

        return $this;
    }

    public function on(string $table): self
    {
        $this->referencedTable = $table;

        return $this;
    }

    public function onDelete(ReferentialAction $action): self
    {
        $this->onDelete = $action;

        return $this;
    }

    public function onUpdate(ReferentialAction $action): self
    {
        $this->onUpdate = $action;

        return $this;
    }

    /**
     * @return list<string>
     */
    public function getReferencedColumns(): array
    {
        return $this->referencedColumns;
    }

    /**
     * @throws SchemaDefinitionException
     */
    public function getReferencedTable(): string
    {
        if ($this->referencedTable === null) {
            throw new SchemaDefinitionException(
                sprintf('The foreign key on (%s) does not say which table it points at: call on().', implode(', ', $this->columns))
            );
        }

        return $this->referencedTable;
    }

    public function getOnDelete(): ?ReferentialAction
    {
        return $this->onDelete;
    }

    public function getOnUpdate(): ?ReferentialAction
    {
        return $this->onUpdate;
    }
}
