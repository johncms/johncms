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

use Johncms\Mail\EmailMessage;

interface EmailQueueInterface
{
    /**
     * Take the next batch of messages for this worker alone.
     *
     * @return list<EmailMessage>
     */
    public function claim(int $limit): array;

    /**
     * Record a delivered message.
     */
    public function markSent(EmailMessage $message): void;

    /**
     * Record an attempt that did not work, and schedule the next one.
     *
     * @return bool Whether another attempt is still to come.
     */
    public function markAttemptFailed(EmailMessage $message, string $error): bool;

    /**
     * Give up on a message for good: nothing about it will change on a later run.
     */
    public function markFailed(EmailMessage $message, string $error): void;

    /**
     * Remove the messages delivered longer than the given number of days ago.
     *
     * @return int The number of rows removed.
     */
    public function pruneSent(int $days): int;
}
