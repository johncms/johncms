<?php

declare(strict_types=1);

namespace Johncms\Modules\Collections\Infrastructure\Persistence;

use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Database\ConcurrencyErrorDetector;
use Illuminate\Database\DeadlockException;
use Illuminate\Database\QueryException;
use PDO;
use Throwable;
use Johncms\Modules\Collections\Domain\Exceptions\CollectionWriteConflictException;
use Johncms\Modules\Collections\Domain\Repository\CollectionTransactionInterface;

final class CollectionTransaction implements CollectionTransactionInterface
{
    public function run(callable $operation): mixed
    {
        // Use the ORM connection so nested writes use savepoints in the caller's transaction.
        try {
            $connection = Capsule::connection();
            \Johncms\Database\EloquentTransactionCallbacks::attach($connection);
            if ($connection->transactionLevel() === 0 && $connection->getPdo()->inTransaction()) {
                return $this->withinPdoTransaction($connection->getPdo(), $operation);
            }
            return $connection->transaction($operation);
        } catch (QueryException | DeadlockException $exception) {
            if ((new ConcurrencyErrorDetector())->causedByConcurrencyError($exception)) {
                throw new CollectionWriteConflictException('The data changed during saving. Retry the entire operation.', previous: $exception);
            }
            throw $exception;
        }
    }
    public function afterCommit(callable $callback): void
    {
        $connection = Capsule::connection();
        \Johncms\Database\EloquentTransactionCallbacks::attach($connection);
        \Johncms\Database\TransactionCallbacks::afterCommit($connection->getPdo(), $callback);
    }

    /**
     * A caller using the core ConnectionInterface owns a PDO transaction outside ORM bookkeeping.
     * @template T
     * @param callable(): T $operation
     * @return T
     */
    private function withinPdoTransaction(PDO $pdo, callable $operation): mixed
    {
        $depth = \Johncms\Database\TransactionCallbacks::depth($pdo);
        if ($depth === 0) {
            throw new \LogicException('Use ConnectionInterface::transaction() to observe the outer PDO commit.');
        }
        $savepoint = 'collection_' . bin2hex(random_bytes(8));
        $pdo->exec('SAVEPOINT ' . $savepoint);
        \Johncms\Database\TransactionCallbacks::begin($pdo);
        try {
            $result = $operation();
        } catch (Throwable $exception) {
            \Johncms\Database\TransactionCallbacks::rollbackTo($pdo, $depth);
            if ($pdo->inTransaction()) {
                $pdo->exec('ROLLBACK TO SAVEPOINT ' . $savepoint);
                $pdo->exec('RELEASE SAVEPOINT ' . $savepoint);
            }
            throw $exception;
        }
        $pdo->exec('RELEASE SAVEPOINT ' . $savepoint);
        \Johncms\Database\TransactionCallbacks::commit($pdo);
        return $result;
    }
}
