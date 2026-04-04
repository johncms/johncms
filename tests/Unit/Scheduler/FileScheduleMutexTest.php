<?php

declare(strict_types=1);

namespace Tests\Unit\Scheduler;

use Johncms\Scheduler\FileScheduleMutex;
use PHPUnit\Framework\TestCase;

final class FileScheduleMutexTest extends TestCase
{
    public function testAcquireReturnsHandleAndSecondAcquireReturnsNullForSameKey(): void
    {
        $mutex = new FileScheduleMutex();
        $key = 'mutex-test-' . uniqid('', true);

        $firstLock = $mutex->acquire($key);
        self::assertIsResource($firstLock);

        $secondLock = $mutex->acquire($key);
        self::assertNull($secondLock);

        $mutex->release($firstLock);
    }

    public function testReleaseAllowsAcquireAgain(): void
    {
        $mutex = new FileScheduleMutex();
        $key = 'mutex-test-' . uniqid('', true);

        $firstLock = $mutex->acquire($key);
        self::assertIsResource($firstLock);
        $mutex->release($firstLock);

        $secondLock = $mutex->acquire($key);
        self::assertIsResource($secondLock);
        $mutex->release($secondLock);
    }
}
