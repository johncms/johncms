<?php

declare(strict_types=1);

namespace Tests\Unit\Modules\Admin\Dashboard;

use Gettext\Translator;
use Gettext\TranslatorFunctions;
use Johncms\Auth\Authorization\CorePermissions;
use Johncms\Modules\Admin\Application\Dashboard\ServerAttentionProvider;
use Johncms\Modules\Admin\Domain\Services\ServerHealth;
use Johncms\Modules\Admin\Domain\Services\ServerStatusInterface;
use Johncms\Scheduler\ScheduleHeartbeatInterface;
use Johncms\View\Dashboard\AttentionItem;
use Johncms\View\Dashboard\AttentionLevel;
use PHPUnit\Framework\TestCase;
use Tests\Support\FakeAccessChecker;

/**
 * The problems of the machine among the lines that need attention: a scheduler that does not run,
 * a disk running out.
 */
final class ServerAttentionProviderTest extends TestCase
{
    protected function setUp(): void
    {
        // Nothing registers a translator in an isolated unit test, and the lines are translated.
        TranslatorFunctions::register(new Translator());
    }

    public function testASchedulerThatRanAMinuteAgoAndARoomyDiskAskForNothing(): void
    {
        self::assertSame([], $this->itemsFor(lastRun: time() - 60, disk: ['free' => 50, 'total' => 100]));
    }

    public function testASchedulerThatNeverRanIsAProblem(): void
    {
        $items = $this->itemsFor(lastRun: null, disk: ['free' => 50, 'total' => 100]);

        self::assertCount(1, $items);
        self::assertSame(AttentionLevel::Danger, $items[0]->level);
        self::assertSame('The task scheduler (cron) has never run', $items[0]->name);
    }

    public function testASchedulerSilentForMoreThanTenMinutesHasStopped(): void
    {
        $items = $this->itemsFor(lastRun: time() - ServerHealth::SCHEDULER_STALE_AFTER - 60, disk: null);

        self::assertSame(['The task scheduler (cron) has stopped'], array_map(static fn (AttentionItem $item): string => $item->name, $items));
    }

    public function testADiskWithLessThanATenthFreeIsAWarning(): void
    {
        $items = $this->itemsFor(lastRun: time(), disk: ['free' => 9, 'total' => 100]);

        self::assertCount(1, $items);
        self::assertSame(AttentionLevel::Warning, $items[0]->level);
    }

    public function testWhoDoesNotManageTheSiteIsNotToldOfTheMachine(): void
    {
        self::assertSame([], $this->itemsFor(lastRun: null, disk: ['free' => 1, 'total' => 100], granted: []));
    }

    /**
     * @param array{free: int, total: int}|null $disk
     * @param list<string>                      $granted
     * @return list<AttentionItem>
     */
    private function itemsFor(?int $lastRun, ?array $disk, array $granted = [CorePermissions::ADMIN_SETTINGS_MANAGE]): array
    {
        $heartbeat = new readonly class ($lastRun) implements ScheduleHeartbeatInterface {
            public function __construct(private ?int $lastRun)
            {
            }

            public function beat(int $time): void
            {
            }

            public function lastBeat(): ?int
            {
                return $this->lastRun;
            }
        };

        $server = new readonly class ($disk) implements ServerStatusInterface {
            /**
             * @param array{free: int, total: int}|null $disk
             */
            public function __construct(private ?array $disk)
            {
            }

            public function databaseVersion(): string
            {
                return 'MariaDB 11.8.5';
            }

            public function disk(): ?array
            {
                return $this->disk;
            }

            public function cacheSize(): int
            {
                return 0;
            }

            public function logsSize(): int
            {
                return 0;
            }
        };

        $provider = new ServerAttentionProvider(new FakeAccessChecker($granted), $heartbeat, $server, new ServerHealth());

        return [...$provider->attentionItems()];
    }
}
