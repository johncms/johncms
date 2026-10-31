<?php

declare(strict_types=1);

namespace Tests\Unit\Scheduler;

use Johncms\Scheduler\FileScheduleHeartbeat;
use PHPUnit\Framework\TestCase;

final class FileScheduleHeartbeatTest extends TestCase
{
    private string $directory;

    protected function setUp(): void
    {
        $this->directory = sys_get_temp_dir() . '/johncms-heartbeat-' . bin2hex(random_bytes(4));
    }

    protected function tearDown(): void
    {
        @unlink($this->directory . '/last-run');
        @rmdir($this->directory);
    }

    public function testASchedulerThatNeverRanHasNoBeat(): void
    {
        self::assertNull((new FileScheduleHeartbeat($this->directory . '/last-run'))->lastBeat());
    }

    public function testTheLastBeatIsRemembered(): void
    {
        (new FileScheduleHeartbeat($this->directory . '/last-run'))->beat(1_000);
        (new FileScheduleHeartbeat($this->directory . '/last-run'))->beat(2_000);

        self::assertSame(2_000, (new FileScheduleHeartbeat($this->directory . '/last-run'))->lastBeat());
    }
}
