<?php

declare(strict_types=1);

namespace Tests\Unit\Database;

use Johncms\Database\ConnectionInterface;
use Johncms\Database\PdoConnection;
use PDO;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class PdoConnectionTest extends TestCase
{
    private PDO $pdo;

    private PdoConnection $connection;

    protected function setUp(): void
    {
        $this->pdo = new PDO('sqlite::memory:');
        $this->pdo->exec('CREATE TABLE notes (id INTEGER PRIMARY KEY, title TEXT, weight INTEGER)');
        $this->pdo->exec("INSERT INTO notes (title, weight) VALUES ('first', 1), ('second', 2), ('third', 2)");

        $this->connection = new PdoConnection($this->pdo);
    }

    public function testSelectAnswersWithAssociativeRows(): void
    {
        $rows = $this->connection->select('SELECT title, weight FROM notes ORDER BY id');

        self::assertSame(
            [
                ['title' => 'first', 'weight' => 1],
                ['title' => 'second', 'weight' => 2],
                ['title' => 'third', 'weight' => 2],
            ],
            $rows
        );
    }

    public function testSelectHonoursBindings(): void
    {
        $rows = $this->connection->select('SELECT title FROM notes WHERE weight = ?', [2]);

        self::assertSame([['title' => 'second'], ['title' => 'third']], $rows);
    }

    public function testSelectOneAnswersWithTheFirstRowOrNull(): void
    {
        self::assertSame(['title' => 'first'], $this->connection->selectOne('SELECT title FROM notes ORDER BY id'));
        self::assertNull($this->connection->selectOne('SELECT title FROM notes WHERE weight = ?', [99]));
    }

    public function testExecuteAnswersWithTheNumberOfAffectedRows(): void
    {
        self::assertSame(2, $this->connection->execute('UPDATE notes SET title = ? WHERE weight = ?', ['changed', 2]));
        self::assertSame(0, $this->connection->execute('DELETE FROM notes WHERE weight = ?', [99]));
    }

    public function testTransactionCommitsAndAnswersWithTheCallbackResult(): void
    {
        $result = $this->connection->transaction(static function (ConnectionInterface $db): string {
            $db->execute("INSERT INTO notes (title, weight) VALUES ('fourth', 4)");

            return 'done';
        });

        self::assertSame('done', $result);
        self::assertSame(4, (int) $this->connection->select('SELECT COUNT(*) AS total FROM notes')[0]['total']);
    }

    public function testTransactionRollsBackOnFailure(): void
    {
        $caught = null;

        try {
            $this->connection->transaction(static function (ConnectionInterface $db): void {
                $db->execute("INSERT INTO notes (title, weight) VALUES ('fourth', 4)");

                throw new RuntimeException('nope');
            });
        } catch (RuntimeException $exception) {
            $caught = $exception->getMessage();
        }

        self::assertSame('nope', $caught, 'The exception should have been re-thrown.');

        self::assertSame(3, (int) $this->connection->select('SELECT COUNT(*) AS total FROM notes')[0]['total']);
        self::assertFalse($this->pdo->inTransaction());
    }

    /**
     * The databases the CMS runs on do not nest transactions, so an inner call joins the open one
     * instead of opening a second: committing the inner one would commit half of the outer.
     */
    public function testAnOpenTransactionIsJoinedRatherThanNested(): void
    {
        $this->connection->transaction(function (ConnectionInterface $outer): void {
            $outer->execute("INSERT INTO notes (title, weight) VALUES ('fourth', 4)");

            $this->connection->transaction(static function (ConnectionInterface $inner): void {
                $inner->execute("INSERT INTO notes (title, weight) VALUES ('fifth', 5)");
            });

            self::assertTrue($this->pdo->inTransaction(), 'The inner call must not have committed the outer transaction.');
        });

        self::assertSame(5, (int) $this->connection->select('SELECT COUNT(*) AS total FROM notes')[0]['total']);
    }
}
