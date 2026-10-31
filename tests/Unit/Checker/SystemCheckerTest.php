<?php

declare(strict_types=1);

namespace Tests\Unit\Checker;

use Gettext\Translator;
use Gettext\TranslatorFunctions;
use Johncms\Checker\CheckProfile;
use Johncms\Checker\Checks\DatabaseVersionCheck;
use Johncms\Checker\Checks\CacheCheck;
use Johncms\Checker\Checks\StorageCheck;
use Johncms\Checker\Checks\PhpRequirementsCheck;
use Johncms\Checker\CheckSeverity;
use Johncms\Checker\CheckStatus;
use Johncms\Checker\DTO\CheckReportDTO;
use Johncms\Checker\DTO\CheckResultDTO;
use Johncms\Checker\SystemChecker;
use Johncms\Checker\SystemCheckInterface;
use LogicException;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use RuntimeException;
use Symfony\Contracts\Service\ServiceProviderInterface;

final class SystemCheckerTest extends TestCase
{
    protected function setUp(): void
    {
        TranslatorFunctions::register(new Translator());
    }

    #[DataProvider('lightProfiles')]
    public function testLightProfilesDoNotConstructDatabaseOrWriteChecks(CheckProfile $profile): void
    {
        $php = $this->createMock(SystemCheckInterface::class);
        $php->method('run')->willReturn([$this->checkResult('php', CheckStatus::Passed)]);
        $locator = $this->createMock(ServiceProviderInterface::class);
        $locator->method('getProvidedServices')->willReturn([
            DatabaseVersionCheck::class => '?', PhpRequirementsCheck::class => '?', CacheCheck::class => '?', StorageCheck::class => '?',
        ]);
        $locator->expects(self::once())->method('get')->with(PhpRequirementsCheck::class)->willReturn($php);

        $report = (new SystemChecker($locator, new NullLogger()))->run($profile);

        self::assertSame(1, $report->evaluated);
        self::assertSame(0, $report->unavailable);
    }

    public static function lightProfiles(): iterable
    {
        yield [CheckProfile::Dashboard];
        yield [CheckProfile::Installer];
    }

    public function testTheExtendedProfileIncludesPageAndWriteChecks(): void
    {
        $locator = $this->createMock(ServiceProviderInterface::class);
        $locator->method('getProvidedServices')->willReturn([PhpRequirementsCheck::class => '?', CacheCheck::class => '?']);
        $php = $this->createMock(SystemCheckInterface::class);
        $php->method('run')->willReturn([$this->checkResult('php', CheckStatus::Passed)]);
        $cache = $this->createMock(SystemCheckInterface::class);
        $cache->method('run')->willReturn([$this->checkResult('cache', CheckStatus::Passed)]);
        $locator->expects(self::exactly(2))->method('get')->willReturnMap([[PhpRequirementsCheck::class, $php], [CacheCheck::class, $cache]]);

        self::assertSame(2, (new SystemChecker($locator, new NullLogger()))->run(CheckProfile::Extended)->evaluated);
    }

    public function testAFailedDependencyIsReportedAndOtherChecksStillRun(): void
    {
        $php = $this->createMock(SystemCheckInterface::class);
        $php->method('run')->willReturn([$this->checkResult('php', CheckStatus::Passed)]);
        $locator = $this->createMock(ServiceProviderInterface::class);
        $locator->method('getProvidedServices')->willReturn([
            DatabaseVersionCheck::class => '?', PhpRequirementsCheck::class => '?',
        ]);
        $locator->method('get')->willReturnCallback(static function (string $id) use ($php): SystemCheckInterface {
            if ($id === DatabaseVersionCheck::class) {
                throw new RuntimeException('Secret connection details');
            }
            return $php;
        });
        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects(self::once())->method('error')->with('System check failed.', self::callback(
            static fn (array $context): bool => $context['check'] === 'database.version' && $context['exception'] instanceof RuntimeException
        ));

        $report = (new SystemChecker($locator, $logger))->run();

        self::assertSame(1, $report->evaluated);
        self::assertSame(1, $report->unavailable);
        self::assertSame(CheckStatus::Passed, $report->sections[0]['checks'][0]->status);
        self::assertStringNotContainsString('Secret', $report->sections[1]['checks'][0]->description);
    }

    public function testAnExceptionDuringARunDoesNotBecomeAPassedCheck(): void
    {
        $check = $this->createMock(SystemCheckInterface::class);
        $check->method('run')->willThrowException(new RuntimeException('Unreadable migrations'));
        $locator = $this->createMock(ServiceProviderInterface::class);
        $locator->method('getProvidedServices')->willReturn([DatabaseVersionCheck::class => '?']);
        $locator->method('get')->willReturn($check);

        $report = (new SystemChecker($locator, new NullLogger()))->run();

        self::assertSame(0, $report->evaluated);
        self::assertSame(1, $report->unavailable);
    }

    public function testDuplicateCheckIdentifiersAreRejected(): void
    {
        $locator = $this->createMock(ServiceProviderInterface::class);
        $locator->method('getProvidedServices')->willReturn(['first' => PhpRequirementsCheck::class, 'second' => PhpRequirementsCheck::class]);
        $locator->expects(self::never())->method('get');

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('Duplicate system check ID');
        (new SystemChecker($locator, new NullLogger()))->run();
    }

    public function testTheSummaryDistinguishesProblemsSkippedChecksAndUnknownResults(): void
    {
        $report = new CheckReportDTO([['title' => 'Test', 'icon' => 'gauge', 'checks' => [
            $this->checkResult('ok', CheckStatus::Passed),
            $this->checkResult('critical', CheckStatus::Failed),
            $this->checkResult('warning', CheckStatus::Failed, CheckSeverity::Warning),
            $this->checkResult('note', CheckStatus::Failed, CheckSeverity::Info),
            $this->checkResult('skipped', CheckStatus::Skipped),
            $this->checkResult('unknown', CheckStatus::Unavailable),
        ]]], 123, 'test');

        self::assertSame([1, 1, 1, 4, 6], [$report->critical, $report->warnings, $report->unavailable, $report->evaluated, $report->total]);
    }

    private function checkResult(string $id, CheckStatus $status, CheckSeverity $severity = CheckSeverity::Critical): CheckResultDTO
    {
        return new CheckResultDTO($id, $id, '', $status, $severity, '');
    }
}
