<?php

declare(strict_types=1);

namespace Johncms\Database;

use Illuminate\Database\Capsule\Manager as Capsule;

/**
 * The transactions of the ORM connection: the queries of the models and of the query builder run
 * on it, so a repository built on either joins the transaction of its caller.
 */
final readonly class EloquentTransactionManager implements TransactionManagerInterface
{
    public function run(callable $operation): mixed
    {
        return Capsule::connection()->transaction($operation);
    }

    public function afterCommit(callable $callback): void
    {
        TransactionCallbacks::afterCommit(Capsule::connection()->getPdo(), $callback);
    }
}
