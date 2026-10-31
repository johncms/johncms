<?php

declare(strict_types=1);

namespace Tests\Unit\Modules\Admin\Dashboard;

use Gettext\Translator;
use Gettext\TranslatorFunctions;
use Johncms\Checker\CheckProfile;
use Johncms\Checker\CheckSeverity;
use Johncms\Checker\CheckStatus;
use Johncms\Checker\DTO\CheckReportDTO;
use Johncms\Checker\DTO\CheckResultDTO;
use Johncms\Checker\SystemCheckerInterface;
use Johncms\Modules\Admin\Application\Dashboard\SystemCheckAttentionProvider;
use Johncms\Modules\Admin\Application\Services\AdminPermissions;
use Johncms\View\Dashboard\AttentionItem;
use Johncms\View\Dashboard\AttentionLevel;
use PHPUnit\Framework\TestCase;
use Tests\Support\FakeAccessChecker;

/**
 * The failed checks of PHP, counted by how much they matter, for whoever may open the system check.
 */
final class SystemCheckAttentionProviderTest extends TestCase
{
    protected function setUp(): void
    {
        // Nothing registers a translator in an isolated unit test, and the lines are translated.
        TranslatorFunctions::register(new Translator());
    }

    public function testTheFailedChecksAreCountedByLevel(): void
    {
        $items = $this->itemsFor([AdminPermissions::SYSTEM_CHECK], extensions: [true, true, false], recommendations: [true]);

        self::assertSame(
            [[AttentionLevel::Danger, 2], [AttentionLevel::Warning, 1]],
            array_map(static fn (AttentionItem $item): array => [$item->level, $item->count], $items)
        );
    }

    public function testAServerThatPassesAsksForNothing(): void
    {
        self::assertSame([], $this->itemsFor([AdminPermissions::SYSTEM_CHECK], extensions: [false], recommendations: [false]));
    }

    public function testWhoMayNotOpenTheSystemCheckIsNotToldOfIt(): void
    {
        self::assertSame([], $this->itemsFor([], extensions: [true], recommendations: [true]));
    }

    /**
     * @param list<string> $granted
     * @param list<bool>   $extensions      Whether each critical check fails.
     * @param list<bool>   $recommendations Whether each recommendation fails.
     * @return list<AttentionItem>
     */
    private function itemsFor(array $granted, array $extensions, array $recommendations): array
    {
        $checks = [];
        foreach ([CheckSeverity::Critical->value => $extensions, CheckSeverity::Warning->value => $recommendations] as $level => $errors) {
            foreach ($errors as $index => $error) {
                $checks[] = new CheckResultDTO(
                    $level . $index,
                    '',
                    '',
                    $error ? CheckStatus::Failed : CheckStatus::Passed,
                    CheckSeverity::from($level),
                    '',
                );
            }
        }
        $report = new CheckReportDTO([['title' => 'PHP', 'icon' => 'gauge', 'checks' => $checks]], 123, 'test');
        $checker = $this->createMock(SystemCheckerInterface::class);
        $checker->expects($granted === [] ? self::never() : self::once())
            ->method('run')->with(CheckProfile::Dashboard)->willReturn($report);

        $provider = new SystemCheckAttentionProvider(new FakeAccessChecker($granted), $checker);

        return [...$provider->attentionItems()];
    }
}
