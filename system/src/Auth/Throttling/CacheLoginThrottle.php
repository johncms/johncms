<?php

/**
 * This file is part of JohnCMS Content Management System.
 *
 * @copyright JohnCMS Community
 * @license   https://opensource.org/licenses/GPL-3.0 GPL-3.0
 * @link      https://johncms.com JohnCMS Project
 */

declare(strict_types=1);

namespace Johncms\Auth\Throttling;

use Illuminate\Contracts\Cache\Repository as CacheRepository;

/**
 * Counts failed sign-in attempts in the cache.
 *
 * The cache rather than a table: these counters are worthless the moment they expire, they are
 * written on every failure, and losing them costs nothing worse than a guesser getting a few
 * more tries after a cache clear.
 *
 * Two thresholds, because they answer different problems. A verification code after a few
 * failures stops a script while barely inconveniencing somebody who mistyped. A wait after many
 * more stops a script that solves verification codes, and the wait grows so that a patient
 * attacker gets slower rather than merely delayed.
 */
final readonly class CacheLoginThrottle implements LoginThrottleInterface
{
    private const PREFIX = 'auth.throttle.';

    /**
     * @param int       $verificationAfter Failures after which a verification code is asked for.
     * @param int       $lockoutAfter      Failures after which attempts are refused outright.
     * @param int       $decay             How long a run of failures is remembered, in seconds.
     * @param list<int> $lockoutSteps      Growing waits, in seconds, applied to successive
     *                                     lockouts. The last one repeats.
     */
    public function __construct(
        private CacheRepository $cache,
        private int $verificationAfter = 3,
        private int $lockoutAfter = 10,
        private int $decay = 900,
        private array $lockoutSteps = [60, 300, 900, 3600],
    ) {
    }

    public function retryAfter(string $key): int
    {
        $until = $this->cache->get($this->lockKey($key));

        if (! is_int($until)) {
            return 0;
        }

        return max(0, $until - time());
    }

    public function requiresVerification(string $key): bool
    {
        return $this->failures($key) >= $this->verificationAfter;
    }

    public function registerFailure(string $key): void
    {
        $failures = $this->failures($key) + 1;
        $this->cache->put($this->failureKey($key), $failures, $this->decay);

        if ($failures < $this->lockoutAfter) {
            return;
        }

        // Every further failure past the threshold moves the wait up a step, so a run that keeps
        // going becomes slower rather than merely postponed by a fixed amount each time.
        $step = min($failures - $this->lockoutAfter, count($this->lockoutSteps) - 1);
        $wait = $this->lockoutSteps[$step];

        $this->cache->put($this->lockKey($key), time() + $wait, $wait);
    }

    public function clear(string $key): void
    {
        $this->cache->forget($this->failureKey($key));
        $this->cache->forget($this->lockKey($key));
    }

    private function failures(string $key): int
    {
        $failures = $this->cache->get($this->failureKey($key));

        return is_int($failures) ? $failures : 0;
    }

    private function failureKey(string $key): string
    {
        // Hashed, because the key carries a login somebody typed: it must not end up as a file
        // name on disk, and it must not be readable in a cache listing.
        return self::PREFIX . 'f.' . hash('xxh128', $key);
    }

    private function lockKey(string $key): string
    {
        return self::PREFIX . 'l.' . hash('xxh128', $key);
    }
}
