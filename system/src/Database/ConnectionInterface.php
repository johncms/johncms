<?php

/**
 * This file is part of JohnCMS Content Management System.
 *
 * @copyright JohnCMS Community
 * @license   https://opensource.org/licenses/GPL-3.0 GPL-3.0
 * @link      https://johncms.com JohnCMS Project
 */

declare(strict_types=1);

namespace Johncms\Database;

use Throwable;

/**
 * Running a query without an ORM behind it.
 *
 * Exists for the code that must keep working when the data layer underneath is replaced —
 * migrations above all. A migration written against a model breaks as soon as the model is
 * refactored, and a migration written a year ago still has to run on somebody's site today.
 * So it gets SQL and this contract, and nothing else.
 */
interface ConnectionInterface
{
    /**
     * @param list<mixed>|array<string, mixed> $bindings
     * @return list<array<string, mixed>>
     */
    public function select(string $sql, array $bindings = []): array;

    /**
     * @param list<mixed>|array<string, mixed> $bindings
     * @return array<string, mixed>|null
     */
    public function selectOne(string $sql, array $bindings = []): ?array;

    /**
     * Runs a statement that changes data and answers with the number of affected rows.
     *
     * @param list<mixed>|array<string, mixed> $bindings
     */
    public function execute(string $sql, array $bindings = []): int;

    /**
     * Runs the callback inside a transaction, committing it on return and rolling it back on any
     * exception. An already open transaction is joined rather than nested: the databases the CMS
     * runs on do not nest them, and pretending otherwise would commit half of the outer one.
     *
     * @template T
     * @param callable(ConnectionInterface): T $callback
     * @return T
     * @throws Throwable
     */
    public function transaction(callable $callback): mixed;
}
