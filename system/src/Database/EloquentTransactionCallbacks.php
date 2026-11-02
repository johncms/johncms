<?php

declare(strict_types=1);

namespace Johncms\Database;

use Illuminate\Database\Connection;
use Illuminate\Database\DatabaseTransactionsManager;
use WeakMap;

/** Bridges the ORM transaction lifecycle without opening a lazy database connection. */
final class EloquentTransactionCallbacks extends DatabaseTransactionsManager
{
    /** @var WeakMap<Connection, self>|null */
    private static ?WeakMap $adapters = null;

    /** @var \WeakReference<Connection> */
    private readonly \WeakReference $connectionRef;

    private function __construct(Connection $connection)
    {
        parent::__construct();
        $this->connectionRef = \WeakReference::create($connection);
    }

    private function connection(): Connection
    {
        return $this->connectionRef->get() ?? throw new \LogicException('The transaction connection has been released.');
    }

    public static function attach(Connection $connection): void
    {
        self::$adapters ??= new WeakMap();
        if (isset(self::$adapters[$connection])) {
            return;
        }
        $adapter = new self($connection);
        self::$adapters[$connection] = $adapter;
        $connection->setTransactionManager($adapter);
        // Tests and embedders may attach after an outer ORM transaction has already begun.
        for ($level = 1; $level <= $connection->transactionLevel(); ++$level) {
            $adapter->begin($connection->getName(), $level);
        }
    }

    public function begin($connection, $level): void
    {
        parent::begin($connection, $level);
        TransactionCallbacks::begin($this->connection()->getPdo());
    }

    public function commit($connection, $levelBeingCommitted, $newTransactionLevel): array
    {
        parent::commit($connection, $levelBeingCommitted, $newTransactionLevel);
        TransactionCallbacks::commit($this->connection()->getPdo());
        return [];
    }

    public function rollback($connection, $newTransactionLevel): void
    {
        try {
            parent::rollback($connection, $newTransactionLevel);
        } finally {
            TransactionCallbacks::rollbackTo($this->connection()->getPdo(), $newTransactionLevel);
        }
    }

    public function addCallback($callback): void
    {
        TransactionCallbacks::afterCommit($this->connection()->getPdo(), $callback);
    }
}
