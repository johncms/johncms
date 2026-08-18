<?php

declare(strict_types=1);

namespace Tests\Unit\Auth\Throttling;

use Johncms\Auth\Throttling\CacheLoginThrottle;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Tests\Support\InMemoryCache;

final class CacheLoginThrottleTest extends TestCase
{
    public function testAFreshKeyIsUnrestricted(): void
    {
        $throttle = $this->throttle();

        self::assertSame(0, $throttle->retryAfter('login:tester'));
        self::assertFalse($throttle->requiresVerification('login:tester'));
    }

    public function testAVerificationCodeIsAskedForAfterTheThreshold(): void
    {
        $throttle = $this->throttle();

        $throttle->registerFailure('login:tester');
        $throttle->registerFailure('login:tester');
        self::assertFalse($throttle->requiresVerification('login:tester'));

        $throttle->registerFailure('login:tester');
        self::assertTrue($throttle->requiresVerification('login:tester'));
    }

    /**
     * Neither walking through logins from one address nor rotating addresses against one login
     * may get around the limit, which only works if the keys are counted apart.
     */
    public function testKeysAreCountedSeparately(): void
    {
        $throttle = $this->throttle();

        $throttle->registerFailure('login:tester');
        $throttle->registerFailure('login:tester');
        $throttle->registerFailure('login:tester');

        self::assertTrue($throttle->requiresVerification('login:tester'));
        self::assertFalse($throttle->requiresVerification('ip:192.0.2.10'));
    }

    public function testAttemptsAreRefusedAfterTheSecondThreshold(): void
    {
        $throttle = $this->throttle();

        for ($attempt = 0; $attempt < 9; $attempt++) {
            $throttle->registerFailure('login:tester');
        }

        self::assertSame(0, $throttle->retryAfter('login:tester'));

        $throttle->registerFailure('login:tester');

        self::assertGreaterThan(0, $throttle->retryAfter('login:tester'));
    }

    /**
     * A run that keeps going has to become slower, not merely postponed by the same amount
     * every time.
     */
    public function testTheWaitGrowsWithFurtherFailures(): void
    {
        $throttle = $this->throttle();

        for ($attempt = 0; $attempt < 10; $attempt++) {
            $throttle->registerFailure('login:tester');
        }
        $first = $throttle->retryAfter('login:tester');

        $throttle->registerFailure('login:tester');
        $second = $throttle->retryAfter('login:tester');

        $throttle->registerFailure('login:tester');
        $third = $throttle->retryAfter('login:tester');

        self::assertGreaterThan($first, $second);
        self::assertGreaterThan($second, $third);
    }

    public function testTheWaitStopsGrowingAtTheLastStep(): void
    {
        $throttle = $this->throttle();

        for ($attempt = 0; $attempt < 30; $attempt++) {
            $throttle->registerFailure('login:tester');
        }

        // The final step of the configured sequence, and not more than that.
        self::assertLessThanOrEqual(3600, $throttle->retryAfter('login:tester'));
        self::assertGreaterThan(900, $throttle->retryAfter('login:tester'));
    }

    public function testClearingForgetsEverythingAboutTheKey(): void
    {
        $throttle = $this->throttle();

        for ($attempt = 0; $attempt < 12; $attempt++) {
            $throttle->registerFailure('login:tester');
        }

        $throttle->clear('login:tester');

        self::assertSame(0, $throttle->retryAfter('login:tester'));
        self::assertFalse($throttle->requiresVerification('login:tester'));
    }

    /**
     * The key carries a login somebody typed: it must not become a file name on disk or show up
     * in a cache listing.
     */
    public function testTheTypedLoginDoesNotAppearInTheCacheKey(): void
    {
        $storage = new ArrayAdapter();
        (new CacheLoginThrottle(InMemoryCache::over($storage)))->registerFailure('login:secret-name');

        foreach (array_keys($storage->getValues()) as $key) {
            self::assertStringNotContainsString('secret-name', (string) $key);
        }
    }

    private function throttle(): CacheLoginThrottle
    {
        return new CacheLoginThrottle(InMemoryCache::create());
    }
}
