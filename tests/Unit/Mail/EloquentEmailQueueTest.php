<?php

declare(strict_types=1);

namespace Tests\Unit\Mail;

use Carbon\Carbon;
use Illuminate\Database\Capsule\Manager as Capsule;
use Johncms\Mail\EmailMessage;
use Johncms\Mail\Queue\EloquentEmailQueue;
use Johncms\Mail\Queue\MailQueueSettings;
use Johncms\Mail\Schema\MailSchema;
use PHPUnit\Framework\TestCase;
use Tests\Support\BootsInMemoryDatabase;

final class EloquentEmailQueueTest extends TestCase
{
    use BootsInMemoryDatabase;

    protected function setUp(): void
    {
        $this->bootDatabase();
        MailSchema::create(Capsule::schema());
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        $this->shutdownDatabase();
    }

    private function queue(?MailQueueSettings $settings = null): EloquentEmailQueue
    {
        return new EloquentEmailQueue($settings ?? new MailQueueSettings());
    }

    private function queueMessage(int $priority = 100): EmailMessage
    {
        return EmailMessage::query()->create(
            [
                'priority' => $priority,
                'locale'   => 'en',
                'template' => '@theme/emails/registration.twig',
                'fields'   => ['email_to' => 'user@example.com'],
            ]
        );
    }

    private function row(EmailMessage $message): EmailMessage
    {
        return EmailMessage::query()->findOrFail($message->id);
    }

    /**
     * @param list<EmailMessage> $messages
     * @return list<int>
     */
    private function ids(array $messages): array
    {
        return array_map(static fn(EmailMessage $message): int => $message->id, $messages);
    }

    public function testTheSchemaCanBeBuiltOnItsOwnAndTwice(): void
    {
        MailSchema::create(Capsule::schema());

        self::assertTrue(Capsule::schema()->hasTable(MailSchema::EMAIL_MESSAGES));
        self::assertTrue(Capsule::schema()->hasColumn(MailSchema::EMAIL_MESSAGES, 'attempts'));
    }

    // -----------------------------------------------------------------------------------
    // Claiming
    // -----------------------------------------------------------------------------------

    public function testClaimTakesTheMostImportantMessagesFirst(): void
    {
        $low = $this->queueMessage(priority: 100);
        $high = $this->queueMessage(priority: 1);

        $claimed = $this->queue()->claim(1);

        self::assertSame([$high->id], $this->ids($claimed));
        self::assertNull($this->row($low)->locked_at);
    }

    /**
     * The point of claiming: the cron of a busy site can fire again while the previous run is
     * still going, and the same message must not be delivered twice.
     */
    public function testASecondWorkerCannotTakeWhatTheFirstOneHolds(): void
    {
        $this->queueMessage();
        $this->queueMessage();

        $first = $this->queue()->claim(5);
        $second = $this->queue()->claim(5);

        self::assertCount(2, $first);
        self::assertSame([], $second);
    }

    public function testAnAbandonedClaimIsPickedUpAgain(): void
    {
        $settings = new MailQueueSettings(lockTimeout: 900);

        Carbon::setTestNow('2026-08-16 12:00:00');
        $message = $this->queueMessage();
        $this->queue($settings)->claim(5);

        // Still within the timeout: the message belongs to the worker that took it.
        Carbon::setTestNow('2026-08-16 12:10:00');
        self::assertSame([], $this->queue($settings)->claim(5));

        // The worker is long gone; the message goes back into circulation.
        Carbon::setTestNow('2026-08-16 12:20:00');
        self::assertSame([$message->id], $this->ids($this->queue($settings)->claim(5)));
    }

    public function testSentAndFailedMessagesAreNotClaimed(): void
    {
        $sent = $this->queueMessage();
        $failed = $this->queueMessage();

        $queue = $this->queue();
        $queue->markSent($sent);
        $queue->markFailed($failed, 'nope');

        self::assertSame([], $queue->claim(5));
    }

    public function testAMessageWaitingForItsNextAttemptIsNotClaimedYet(): void
    {
        $settings = new MailQueueSettings(maxAttempts: 3, retryDelays: [60]);

        Carbon::setTestNow('2026-08-16 12:00:00');
        $message = $this->queueMessage();
        $queue = $this->queue($settings);
        $queue->claim(5);
        $queue->markAttemptFailed($message, 'the server is down');

        Carbon::setTestNow('2026-08-16 12:00:30');
        self::assertSame([], $this->queue($settings)->claim(5));

        Carbon::setTestNow('2026-08-16 12:01:30');
        self::assertCount(1, $this->queue($settings)->claim(5));
    }

    public function testClaimWithoutRoomTakesNothing(): void
    {
        $this->queueMessage();

        self::assertSame([], $this->queue()->claim(0));
    }

    // -----------------------------------------------------------------------------------
    // Recording the outcome
    // -----------------------------------------------------------------------------------

    public function testASentMessageIsStampedAndReleased(): void
    {
        Carbon::setTestNow('2026-08-16 12:00:00');
        $message = $this->queueMessage();

        $this->queue()->markSent($message);

        $row = $this->row($message);
        self::assertNotNull($row->getRawOriginal('sent_at'));
        self::assertNull($row->locked_at);
        self::assertNull($row->locked_by);
    }

    /**
     * The failure that used to lose mail: a transport error stamped the message as sent, so it was
     * never tried again and nobody could tell it had not arrived.
     */
    public function testAFailedAttemptSchedulesTheNextOneInsteadOfMarkingItSent(): void
    {
        $settings = new MailQueueSettings(maxAttempts: 3, retryDelays: [60, 300]);
        Carbon::setTestNow('2026-08-16 12:00:00');

        $message = $this->queueMessage();
        $willRetry = $this->queue($settings)->markAttemptFailed($message, 'Connection refused');

        self::assertTrue($willRetry);

        $row = $this->row($message);
        self::assertNull($row->getRawOriginal('sent_at'));
        self::assertNull($row->getRawOriginal('failed_at'));
        self::assertSame(1, $row->attempts);
        self::assertSame('Connection refused', $row->last_error);
        self::assertSame('2026-08-16 12:01:00', (string) $row->getRawOriginal('available_at'));
    }

    public function testTheDelayGrowsWithEachAttempt(): void
    {
        $settings = new MailQueueSettings(maxAttempts: 5, retryDelays: [60, 300]);
        Carbon::setTestNow('2026-08-16 12:00:00');

        $message = $this->queueMessage();
        $queue = $this->queue($settings);

        $queue->markAttemptFailed($message, 'first');
        self::assertSame('2026-08-16 12:01:00', (string) $this->row($message)->getRawOriginal('available_at'));

        $queue->markAttemptFailed($this->row($message), 'second');
        self::assertSame('2026-08-16 12:05:00', (string) $this->row($message)->getRawOriginal('available_at'));

        // The last configured delay applies to every further attempt.
        $queue->markAttemptFailed($this->row($message), 'third');
        self::assertSame('2026-08-16 12:05:00', (string) $this->row($message)->getRawOriginal('available_at'));
    }

    public function testTheAttemptsRunOut(): void
    {
        $settings = new MailQueueSettings(maxAttempts: 2, retryDelays: [60]);
        $message = $this->queueMessage();
        $queue = $this->queue($settings);

        self::assertTrue($queue->markAttemptFailed($message, 'first'));
        self::assertFalse($queue->markAttemptFailed($this->row($message), 'second'));

        $row = $this->row($message);
        self::assertNotNull($row->getRawOriginal('failed_at'));
        self::assertNull($row->getRawOriginal('sent_at'));
        self::assertSame(2, $row->attempts);
        self::assertSame('second', $row->last_error);
    }

    public function testALongErrorIsCutDownToSomethingReadable(): void
    {
        $message = $this->queueMessage();

        $this->queue()->markFailed($message, str_repeat('e', 5000));

        self::assertSame(1000, mb_strlen((string) $this->row($message)->last_error));
    }

    // -----------------------------------------------------------------------------------
    // Cleaning up
    // -----------------------------------------------------------------------------------

    public function testPruneRemovesOldDeliveredMessagesOnly(): void
    {
        Carbon::setTestNow('2026-07-01 12:00:00');
        $oldSent = $this->queueMessage();
        $oldFailed = $this->queueMessage();
        $queue = $this->queue();
        $queue->markSent($oldSent);
        $queue->markFailed($oldFailed, 'undeliverable');

        Carbon::setTestNow('2026-08-16 12:00:00');
        $recentSent = $this->queueMessage();
        $queue->markSent($recentSent);
        $pending = $this->queueMessage();

        $removed = $queue->pruneSent(30);

        self::assertSame(1, $removed);
        self::assertNull(EmailMessage::query()->find($oldSent->id));
        self::assertNotNull(EmailMessage::query()->find($oldFailed->id));
        self::assertNotNull(EmailMessage::query()->find($recentSent->id));
        self::assertNotNull(EmailMessage::query()->find($pending->id));
    }

    public function testPruneKeepsEverythingWhenRetentionIsOff(): void
    {
        $message = $this->queueMessage();
        $queue = $this->queue();
        $queue->markSent($message);

        self::assertSame(0, $queue->pruneSent(0));
        self::assertNotNull(EmailMessage::query()->find($message->id));
    }
}
