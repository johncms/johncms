<?php

declare(strict_types=1);

namespace Tests\Unit\Modules\Admin\Infrastructure;

use Johncms\Modules\Admin\Infrastructure\Server\LocalServerStatus;
use PDO;
use PHPUnit\Framework\TestCase;

final class LocalServerStatusTest extends TestCase
{
    public function testTheDatabaseServerIsNamedWithItsVersion(): void
    {
        $status = new LocalServerStatus(new PDO('sqlite::memory:'));

        self::assertMatchesRegularExpression('/^SQLite \d+\.\d+\.\d+$/', $status->databaseVersion());
    }

    public function testTheDiskIsMeasuredWhereTheSiteLies(): void
    {
        $disk = (new LocalServerStatus(new PDO('sqlite::memory:')))->disk();

        self::assertNotNull($disk);
        self::assertGreaterThan(0, $disk['total']);
        self::assertLessThanOrEqual($disk['total'], $disk['free']);
    }
}
