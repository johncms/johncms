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

use PDO;
use Throwable;

/**
 * The connection contract over the PDO the rest of the system already shares.
 *
 * Deliberately built on PDO rather than on the Eloquent connection: this is the half of the data
 * layer that has to outlive the ORM, so it must not depend on it.
 */
final readonly class PdoConnection implements ConnectionInterface
{
    public function __construct(private PDO $pdo)
    {
    }

    public function select(string $sql, array $bindings = []): array
    {
        $statement = $this->pdo->prepare($sql);
        $statement->execute($bindings === [] ? null : $bindings);

        /** @var list<array<string, mixed>> $rows */
        $rows = $statement->fetchAll(PDO::FETCH_ASSOC);

        return $rows;
    }

    public function selectOne(string $sql, array $bindings = []): ?array
    {
        $statement = $this->pdo->prepare($sql);
        $statement->execute($bindings === [] ? null : $bindings);

        /** @var array<string, mixed>|false $row */
        $row = $statement->fetch(PDO::FETCH_ASSOC);

        return $row === false ? null : $row;
    }

    public function execute(string $sql, array $bindings = []): int
    {
        $statement = $this->pdo->prepare($sql);
        $statement->execute($bindings === [] ? null : $bindings);

        return $statement->rowCount();
    }

    public function transaction(callable $callback): mixed
    {
        if ($this->pdo->inTransaction()) {
            return $callback($this);
        }

        $this->pdo->beginTransaction();

        try {
            $result = $callback($this);
        } catch (Throwable $exception) {
            $this->pdo->rollBack();

            throw $exception;
        }

        $this->pdo->commit();

        return $result;
    }
}
