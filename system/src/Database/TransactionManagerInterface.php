<?php

declare(strict_types=1);

namespace Johncms\Database;

/**
 * A unit of work on the database of the site, for a service that has to change several tables
 * together and do something else only once they are written.
 */
interface TransactionManagerInterface
{
    /**
     * Run the operation in a transaction: committed when it returns, rolled back when it throws.
     * Inside an outer transaction it becomes a savepoint of that one.
     *
     * @template T
     * @param callable(): T $operation
     * @return T
     */
    public function run(callable $operation): mixed;

    /**
     * Run the callback once the outermost transaction commits; dropped when it rolls back, and
     * run at once when there is no transaction. For what cannot be undone, removing a file.
     *
     * @param callable(): void $callback
     */
    public function afterCommit(callable $callback): void;
}
