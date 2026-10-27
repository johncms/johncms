<?php

declare(strict_types=1);

namespace Tests\Support;

use Johncms\Mail\Queue\MailQueueInterface;
use Johncms\Mail\Queue\QueuedEmailDTO;

/**
 * Keeps the queued letters in memory, so a test can assert what would have been sent without a
 * queue table or a mailer behind it.
 */
final class RecordingMailQueue implements MailQueueInterface
{
    /** @var list<QueuedEmailDTO> */
    private array $emails = [];

    public function push(QueuedEmailDTO $email): void
    {
        $this->emails[] = $email;
    }

    /**
     * @return list<QueuedEmailDTO> The letters in the order they were queued.
     */
    public function emails(): array
    {
        return $this->emails;
    }
}
