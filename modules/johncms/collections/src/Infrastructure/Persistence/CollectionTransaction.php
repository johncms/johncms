<?php

declare(strict_types=1);

namespace Johncms\Modules\Collections\Infrastructure\Persistence;

use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Database\ConcurrencyErrorDetector;
use Illuminate\Database\DeadlockException;
use Illuminate\Database\QueryException;
use Johncms\Database\EloquentTransactionCallbacks;
use Johncms\Database\TransactionCallbacks;
use Johncms\Modules\Collections\Domain\Exceptions\CollectionWriteConflictException;
use Johncms\Modules\Collections\Domain\Repository\CollectionTransactionInterface;
use LogicException;
use PDO;
use Throwable;

final class CollectionTransaction implements CollectionTransactionInterface
{
    public function run(callable $operation): mixed
    {
        // Use the ORM connection so nested writes use savepoints in the caller's transaction.
        try {
            $connection = Capsule::connection();
            EloquentTransactionCallbacks::attach($connection);
            if ($connection->transactionLevel() === 0 && $connection->getPdo()->inTransaction()) {
                return $this->withinPdoTransaction($connection->getPdo(), $operation);
            }
            return $connection->transaction($operation);
        } catch (QueryException | DeadlockException $exception) {
            if ((new ConcurrencyErrorDetector())->causedByConcurrencyError($exception)) {
                throw new CollectionWriteConflictException(message: 'The data changed during saving. Retry the entire operation.', previous: $exception);
            }
            throw $exception;
        }
    }
    public function afterCommit(callable $callback): void
    {
        $connection = Capsule::connection();
        EloquentTransactionCallbacks::attach($connection);
        TransactionCallbacks::afterCommit($connection->getPdo(), $callback);
    }

    /**
     * A caller using the core ConnectionInterface owns a PDO transaction outside ORM bookkeeping.
     * @template T
     * @param callable(): T $operation
     * @return T
     */
    private function withinPdoTransaction(PDO $pdo, callable $operation): mixed
    {
        $depth = TransactionCallbacks::depth($pdo);
        if ($depth === 0) {
            throw new LogicException('Use ConnectionInterface::transaction() to observe the outer PDO commit.');
        }
        $savepoint = 'collection_' . bin2hex(random_bytes(8));
        $pdo->exec('SAVEPOINT ' . $savepoint);
        TransactionCallbacks::begin($pdo);
        try {
            $result = $operation();
        } catch (Throwable $exception) {
            TransactionCallbacks::rollbackTo($pdo, $depth);
            if ($pdo->inTransaction()) {
                $pdo->exec('ROLLBACK TO SAVEPOINT ' . $savepoint);
                $pdo->exec('RELEASE SAVEPOINT ' . $savepoint);
            }
            throw $exception;
        }
        $pdo->exec('RELEASE SAVEPOINT ' . $savepoint);
        TransactionCallbacks::commit($pdo);
        return $result;
    }
}
