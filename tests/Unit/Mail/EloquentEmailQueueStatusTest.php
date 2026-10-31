<?php

declare(strict_types=1);

namespace Tests\Unit\Mail;

use Johncms\Mail\Queue\EloquentEmailQueueStatus;
use PHPUnit\Framework\TestCase;
use Tests\Support\BootsInMemoryDatabase;
use Tests\Support\Fixture;
use Tests\Support\RunsMigrations;

final class EloquentEmailQueueStatusTest extends TestCase
{
    use BootsInMemoryDatabase;
    use RunsMigrations;

    protected function setUp(): void
    {
        $this->bootDatabase();
        $this->migrate('system', 'initial_mail_schema');
    }

    protected function tearDown(): void
    {
        $this->shutdownDatabase();
    }

    public function testSentFailedScheduledAndOverdueMessagesAreDistinguished(): void
    {
        $now = time();
        $old = gmdate('Y-m-d H:i:s', $now - 7200);
        $future = gmdate('Y-m-d H:i:s', $now + 3600);
        Fixture::insert('email_messages', ['created_at' => $old]);
        Fixture::insert('email_messages', ['created_at' => $old, 'failed_at' => $old]);
        Fixture::insert('email_messages', ['created_at' => $old, 'sent_at' => $old]);
        Fixture::insert('email_messages', ['created_at' => $old, 'available_at' => $future]);
        Fixture::insert('email_messages', ['created_at' => gmdate('Y-m-d H:i:s', $now)]);

        self::assertSame(['failed' => 1, 'overdue' => 1], (new EloquentEmailQueueStatus())->counts($now - 3600));
    }
}
