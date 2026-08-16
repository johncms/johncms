<?php

/**
 * This file is part of JohnCMS Content Management System.
 *
 * @copyright JohnCMS Community
 * @license   https://opensource.org/licenses/GPL-3.0 GPL-3.0
 * @link      https://johncms.com JohnCMS Project
 */

declare(strict_types=1);

namespace Johncms\Mail\Queue;

/**
 * How stubborn the queue is: how often a message that could not be delivered is tried again,
 * when an unfinished claim is considered abandoned, and how long delivered messages are kept.
 */
final readonly class MailQueueSettings
{
    /**
     * @param int $maxAttempts How many delivery attempts a message gets before it is given up on.
     * @param list<int> $retryDelays Seconds to wait before attempt 2, 3, ...; the last value
     *                               applies to every further attempt.
     * @param int $lockTimeout Seconds after which the claim of a worker is considered abandoned.
     * @param int $keepSentDays How long a delivered message stays in the table; 0 keeps it forever.
     */
    public function __construct(
        public int $maxAttempts = 3,
        public array $retryDelays = [60, 300, 900],
        public int $lockTimeout = 900,
        public int $keepSentDays = 30,
    ) {
    }

    public static function fromConfig(): self
    {
        $config = config('mail')['queue'] ?? [];

        $defaults = new self();

        $retryDelays = [];
        foreach ((array) ($config['retry_delays'] ?? $defaults->retryDelays) as $delay) {
            $retryDelays[] = max(0, (int) $delay);
        }

        return new self(
            maxAttempts: max(1, (int) ($config['max_attempts'] ?? $defaults->maxAttempts)),
            retryDelays: $retryDelays === [] ? $defaults->retryDelays : $retryDelays,
            lockTimeout: max(1, (int) ($config['lock_timeout'] ?? $defaults->lockTimeout)),
            keepSentDays: max(0, (int) ($config['keep_sent_days'] ?? $defaults->keepSentDays)),
        );
    }

    /**
     * How long to wait before the given attempt number is made.
     */
    public function retryDelay(int $attempts): int
    {
        if ($attempts < 1) {
            return $this->retryDelays[0];
        }

        return $this->retryDelays[$attempts - 1] ?? $this->retryDelays[array_key_last($this->retryDelays)];
    }
}
