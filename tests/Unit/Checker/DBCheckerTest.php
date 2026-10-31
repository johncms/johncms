<?php

declare(strict_types=1);

namespace Tests\Unit\Checker;

use Illuminate\Database\Connection;
use Johncms\Checker\DBChecker;
use Johncms\Checker\Exceptions\DatabaseCheckException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class DBCheckerTest extends TestCase
{
    #[DataProvider('versions')]
    public function testTheServerVersionIsComparedWithItsRequirement(string $version, string $server, bool $fails): void
    {
        $connection = $this->createMock(Connection::class);
        $connection->expects(self::once())->method('select')->with('SELECT VERSION() as ver;')->willReturn([(object) ['ver' => $version]]);

        $result = $this->checker($connection)->versionInfo();

        self::assertSame($version, $result['version_raw']);
        self::assertSame($server, $result['server_name']);
        self::assertSame($fails, $result['error']);
    }

    public static function versions(): iterable
    {
        yield 'old MySQL' => ['5.6.51', 'MySQL', true];
        yield 'minimum MySQL' => ['5.7.0', 'MySQL', false];
        yield 'current MySQL' => ['8.4.1-commercial', 'MySQL', false];
        yield 'old MariaDB' => ['10.1.48-MariaDB', 'MariaDB', true];
        yield 'minimum MariaDB' => ['10.2.0-MariaDB', 'MariaDB', false];
        yield 'current MariaDB' => ['11.8.5-MariaDB-ubu2404', 'MariaDB', false];
        yield 'MariaDB compatibility prefix' => ['5.5.5-10.11.6-MariaDB', 'MariaDB', false];
        yield 'old MariaDB compatibility prefix' => ['5.5.5-10.1.48-MariaDB', 'MariaDB', true];
    }

    public function testAnUnrecognisedVersionIsAnExplicitFailureToCheck(): void
    {
        $connection = $this->createMock(Connection::class);
        $connection->method('select')->willReturn([(object) ['ver' => 'unknown']]);

        $this->expectException(DatabaseCheckException::class);
        $this->checker($connection)->versionInfo();
    }

    #[DataProvider('databaseValues')]
    public function testTheDriverMustReturnAnIntegerWithoutCreatingATable(?object $row, bool $passes): void
    {
        $connection = $this->createMock(Connection::class);
        $connection->expects(self::once())->method('selectOne')->with('SELECT 666 AS test_int_col')->willReturn($row);
        $connection->expects(self::never())->method('statement');

        self::assertSame($passes, $this->checker($connection)->checkMysqlnd());
    }

    public static function databaseValues(): iterable
    {
        yield 'integer' => [(object) ['test_int_col' => 666], true];
        yield 'string' => [(object) ['test_int_col' => '666'], false];
        yield 'no row' => [null, false];
    }

    private function checker(Connection $connection): DBChecker
    {
        return new class ($connection) extends DBChecker {
            public function __construct(Connection $connection)
            {
                $this->connection = $connection;
            }
        };
    }
}
