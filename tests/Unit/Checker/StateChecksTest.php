<?php

declare(strict_types=1);

namespace Tests\Unit\Checker;

use Gettext\Translator;
use Gettext\TranslatorFunctions;
use Johncms\Auth\Password\LegacyPasswordAuditInterface;
use Johncms\Checker\Checks\LegacyPasswordsCheck;
use Johncms\Checker\Checks\MailQueueCheck;
use Johncms\Checker\Checks\PendingMigrationsCheck;
use Johncms\Checker\CheckSeverity;
use Johncms\Checker\CheckStatus;
use Johncms\Database\Migrations\MigrationFile;
use Johncms\Database\Migrations\PendingMigrationsProviderInterface;
use Johncms\Mail\Queue\EmailQueueStatusInterface;
use Johncms\Modules\Admin\Application\SystemChecks\SchedulerCheck;
use Johncms\Modules\Admin\Domain\Services\ServerHealth;
use Johncms\Scheduler\ScheduleHeartbeatInterface;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class StateChecksTest extends TestCase
{
    protected function setUp(): void
    {
        TranslatorFunctions::register(new Translator());
    }

    public function testPendingMigrationsAreCritical(): void
    {
        $provider = $this->createMock(PendingMigrationsProviderInterface::class);
        $provider->method('pending')->willReturn([new MigrationFile('system', '2026_01_01_000000', 'example', '/example.php')]);
        $result = (new PendingMigrationsCheck($provider))->run()[0];

        self::assertSame(CheckStatus::Failed, $result->status);
        self::assertSame(CheckSeverity::Critical, $result->severity);
        self::assertSame('1', $result->value);
    }

    public function testMigrationReadFailuresPropagateToTheRunner(): void
    {
        $provider = $this->createMock(PendingMigrationsProviderInterface::class);
        $provider->method('pending')->willThrowException(new RuntimeException('Unreadable migration'));
        $this->expectException(RuntimeException::class);
        (new PendingMigrationsCheck($provider))->run();
    }

    #[DataProvider('heartbeats')]
    public function testCronUsesTheDashboardHealthRule(?int $age, CheckStatus $expected): void
    {
        $heartbeat = $this->createMock(ScheduleHeartbeatInterface::class);
        $heartbeat->method('lastBeat')->willReturn($age === null ? null : time() - $age);
        self::assertSame($expected, (new SchedulerCheck($heartbeat, new ServerHealth()))->run()[0]->status);
    }

    public static function heartbeats(): iterable
    {
        yield 'never started' => [null, CheckStatus::Failed];
        yield 'recently ran' => [60, CheckStatus::Passed];
        yield 'stopped' => [ServerHealth::SCHEDULER_STALE_AFTER + 60, CheckStatus::Failed];
    }

    public function testLegacyPasswordsRemainInformational(): void
    {
        $audit = $this->createMock(LegacyPasswordAuditInterface::class);
        $audit->method('countAccountsOnLegacyHash')->willReturn(3);
        $audit->method('countAccounts')->willReturn(10);
        $result = (new LegacyPasswordsCheck($audit))->run()[0];
        self::assertSame(CheckStatus::Failed, $result->status);
        self::assertSame(CheckSeverity::Info, $result->severity);
        self::assertSame('3 / 10', $result->value);
    }

    public function testTheMailQueueReportsFailedAndOverdueMessagesSeparately(): void
    {
        $queue = $this->createMock(EmailQueueStatusInterface::class);
        $queue->expects(self::once())->method('counts')->with(self::callback(static fn (int $cutoff): bool => abs(time() - 3600 - $cutoff) < 5))
            ->willReturn(['failed' => 2, 'overdue' => 0]);
        $results = (new MailQueueCheck($queue))->run();
        self::assertSame([CheckStatus::Failed, CheckStatus::Passed], array_column($results, 'status'));
    }
}
