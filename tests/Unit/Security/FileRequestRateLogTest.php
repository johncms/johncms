<?php

declare(strict_types=1);

namespace Tests\Unit\Security;

use Johncms\Security\FileRequestRateLog;
use PHPUnit\Framework\TestCase;

final class FileRequestRateLogTest extends TestCase
{
    private string $cacheFile;

    protected function setUp(): void
    {
        $this->cacheFile = sys_get_temp_dir() . '/johncms-request-rate-' . bin2hex(random_bytes(4)) . '.cache';
    }

    protected function tearDown(): void
    {
        if (file_exists($this->cacheFile)) {
            unlink($this->cacheFile);
        }
    }

    public function testEveryRequestIsRecorded(): void
    {
        $log = $this->makeLog();
        $log->record('10.0.0.1');
        $log->record('10.0.0.1');
        $log->record('10.0.0.2');

        self::assertSame(['10.0.0.1', '10.0.0.1', '10.0.0.2'], $this->makeLog()->recentAddresses());
    }

    public function testTheReaderSeesWhatAnotherProcessWrote(): void
    {
        $this->makeLog()->record('10.0.0.1');

        self::assertSame(['10.0.0.1'], $this->makeLog()->recentAddresses());
    }

    public function testEntriesOlderThanTheRetentionWindowAreDropped(): void
    {
        file_put_contents(
            $this->cacheFile,
            pack('LL', ip2long('10.0.0.9'), time() - 61) . pack('LL', ip2long('10.0.0.8'), time() - 10)
        );

        $log = $this->makeLog();
        $log->record('10.0.0.1');

        self::assertSame(['10.0.0.8', '10.0.0.1'], $log->recentAddresses());
        // The rewrite is what actually removes them, so a fresh reader must agree.
        self::assertSame(['10.0.0.8', '10.0.0.1'], $this->makeLog()->recentAddresses());
    }

    public function testAnEmptyLogReadsAsAnEmptyList(): void
    {
        self::assertSame([], $this->makeLog()->recentAddresses());
    }

    /**
     * The record format holds an unsigned-int IPv4 address, so anything else degrades to the
     * loopback address — the behaviour this log had while it lived in Environment.
     */
    public function testAnAddressThatIsNotIpv4IsLoggedAsLoopback(): void
    {
        $log = $this->makeLog();
        $log->record('2001:db8::1');
        $log->record('');

        self::assertSame(['127.0.0.1', '127.0.0.1'], $log->recentAddresses());
    }

    private function makeLog(): FileRequestRateLog
    {
        return new FileRequestRateLog($this->cacheFile);
    }
}
