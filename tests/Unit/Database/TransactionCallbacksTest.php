<?php

declare(strict_types=1);

namespace Tests\Unit\Database;

use Illuminate\Database\Capsule\Manager as Capsule;
use Johncms\Database\AfterCommitException;
use Johncms\Database\PdoConnection;
use Johncms\Modules\Collections\Infrastructure\Persistence\CollectionTransaction;
use PHPUnit\Framework\TestCase;
use Tests\Support\BootsInMemoryDatabase;

final class TransactionCallbacksTest extends TestCase
{
    use BootsInMemoryDatabase;

    private array $events = [];
    private CollectionTransaction $transaction;

    protected function setUp(): void
    {
        $this->bootDatabase();
        Capsule::connection()->statement('CREATE TABLE effects (id INTEGER PRIMARY KEY)');
        $this->transaction = new CollectionTransaction();
        $this->events = [];
    }

    protected function tearDown(): void
    {
        $this->shutdownDatabase();
    }

    private function record(string $event): void
    {
        $this->events[] = $event;
    }

    public function testCommitWaitsForAnAlreadyOpenOrmTransaction(): void
    {
        Capsule::connection()->beginTransaction();
        $this->transaction->run(function (): void {
            $this->transaction->afterCommit(fn () => $this->record('committed'));
        });
        self::assertSame([], $this->events);
        Capsule::connection()->commit();
        self::assertSame(['committed'], $this->events);
    }

    public function testOuterRollbackDiscardsCallbacksFromCommittedSavepoints(): void
    {
        Capsule::connection()->beginTransaction();
        $this->transaction->run(function (): void {
            $this->transaction->afterCommit(fn () => $this->record('discarded'));
        });
        Capsule::connection()->rollBack();
        $this->transaction->run(static fn () => null);
        self::assertSame([], $this->events);
    }

    public function testFailedNestedOperationDiscardsOnlyItsOwnCallbacks(): void
    {
        $this->transaction->run(function (): void {
            $this->transaction->afterCommit(fn () => $this->record('first'));
            try {
                $this->transaction->run(function (): void {
                    $this->transaction->afterCommit(fn () => $this->record('discarded'));
                    throw new \LogicException('Abort inner transaction.');
                });
            } catch (\LogicException) {
            }
            $this->transaction->afterCommit(fn () => $this->record('last'));
        });
        self::assertSame(['first', 'last'], $this->events);
    }

    public function testCorePdoTransactionOwnsTheFinalCommit(): void
    {
        (new PdoConnection(Capsule::connection()->getPdo()))->transaction(function (): void {
            $this->transaction->run(function (): void {
                $this->transaction->afterCommit(fn () => $this->record('committed'));
            });
            self::assertSame([], $this->events);
        });
        self::assertSame(['committed'], $this->events);
    }

    public function testCorePdoRollbackDiscardsDerivedWork(): void
    {
        try {
            (new PdoConnection(Capsule::connection()->getPdo()))->transaction(function (): void {
                $this->transaction->run(function (): void {
                    $this->transaction->afterCommit(fn () => $this->record('discarded'));
                    Capsule::table('effects')->insert(['id' => 1]);
                });
                throw new \LogicException('Abort outer transaction.');
            });
        } catch (\LogicException $exception) {
            self::assertSame('Abort outer transaction.', $exception->getMessage());
        }
        self::assertSame([], $this->events);
        self::assertSame(0, Capsule::table('effects')->count());
    }

    public function testPostCommitFailureKeepsDataAndDoesNotSkipOtherCallbacks(): void
    {
        try {
            $this->transaction->run(function (): void {
                Capsule::table('effects')->insert(['id' => 1]);
                $this->transaction->afterCommit(static function (): void {
                    throw new \RuntimeException('Derived action failed.');
                });
                $this->transaction->afterCommit(fn () => $this->record('still executed'));
            });
            self::fail('Derived failures must be reported.');
        } catch (AfterCommitException $exception) {
            self::assertCount(1, $exception->failures);
            self::assertSame('Derived action failed.', $exception->failures[0]->getMessage());
        }
        self::assertSame(['still executed'], $this->events);
        self::assertSame(1, Capsule::table('effects')->count());
        self::assertFalse(Capsule::connection()->getPdo()->inTransaction());
    }

    public function testUnmanagedRawPdoTransactionIsRejectedBeforeMutation(): void
    {
        $pdo = Capsule::connection()->getPdo();
        $pdo->beginTransaction();
        try {
            $this->transaction->run(static fn () => Capsule::table('effects')->insert(['id' => 1]));
            self::fail('An unobservable outer commit must not be accepted.');
        } catch (\LogicException) {
            self::assertSame(0, Capsule::table('effects')->count());
        } finally {
            $pdo->rollBack();
        }
    }
}
