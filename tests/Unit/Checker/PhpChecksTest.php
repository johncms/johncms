<?php

declare(strict_types=1);

namespace Tests\Unit\Checker;

use Gettext\Translator;
use Gettext\TranslatorFunctions;
use Johncms\Checker\Checks\DebugModeCheck;
use Johncms\Checker\Checks\OpcacheCheck;
use Johncms\Checker\Checks\PhpRequirementsCheck;
use Johncms\Checker\Checks\UploadLimitsCheck;
use Johncms\Checker\CheckSeverity;
use Johncms\Checker\CheckStatus;
use Johncms\Checker\PhpEnvironmentInterface;
use Johncms\Checker\PlatformRequirements;
use Johncms\Config\ConfigRepository;
use Johncms\Http\UploadLimits;
use Johncms\Image\EditorImageSettings;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class PhpChecksTest extends TestCase
{
    private array $config;

    protected function setUp(): void
    {
        TranslatorFunctions::register(new Translator());
        $this->config = ConfigRepository::all();
        ConfigRepository::init(['johncms' => ['editor_images' => ['max_size' => 5120]]]);
    }

    protected function tearDown(): void
    {
        ConfigRepository::init($this->config);
    }

    #[DataProvider('versions')]
    public function testThePhpVersionMustSatisfyTheWholeSupportedRange(string $version, CheckStatus $expected): void
    {
        $result = (new PhpRequirementsCheck($this->php(version: $version)))->run();
        self::assertSame($expected, $result[0]->status);
    }

    public static function versions(): iterable
    {
        yield ['8.3.30', CheckStatus::Failed];
        yield ['8.4.0', CheckStatus::Passed];
        yield ['8.5.1', CheckStatus::Passed];
        yield ['9.0.0', CheckStatus::Failed];
    }

    public function testPlatformRequirementsStayAlignedWithComposer(): void
    {
        $composer = json_decode(file_get_contents(ROOT_PATH . 'composer.json'), true, flags: JSON_THROW_ON_ERROR);
        self::assertSame($composer['require']['php'], PlatformRequirements::PHP);
        $extensions = [];
        foreach ($composer['require'] as $name => $constraint) {
            if (str_starts_with($name, 'ext-')) {
                $extensions[] = substr($name, 4);
            }
        }
        self::assertEqualsCanonicalizing($extensions, PlatformRequirements::EXTENSIONS);
    }

    public function testImagickDoesNotHideAMissingGdRequirement(): void
    {
        $results = (new PhpRequirementsCheck($this->php(missing: ['gd'])))->run();
        $failed = array_values(array_filter($results, static fn ($result): bool => $result->status === CheckStatus::Failed));
        self::assertSame(['php.extension.gd'], array_column($failed, 'id'));
    }

    public function testMissingOpcacheDoesNotProduceTwoWarnings(): void
    {
        $results = (new OpcacheCheck($this->php(missing: ['Zend OPcache'])))->run();
        self::assertSame([CheckStatus::Failed, CheckStatus::Skipped], array_column($results, 'status'));
    }

    public function testOpcacheAcceptsBooleanIniValues(): void
    {
        $results = (new OpcacheCheck($this->php(ini: ['opcache.enable' => 'On'])))->run();
        self::assertSame(CheckStatus::Passed, $results[1]->status);
    }

    public function testDisabledCliOpcacheIsInformational(): void
    {
        $results = (new OpcacheCheck($this->php(ini: ['opcache.enable' => '1', 'opcache.enable_cli' => '0'], sapi: 'cli')))->run();
        self::assertSame(CheckStatus::Failed, $results[1]->status);
        self::assertSame(CheckSeverity::Info, $results[1]->severity);
    }

    #[DataProvider('uploadLimits')]
    public function testTheEditorLimitAndFormOverheadAreChecked(string $upload, string $post, CheckStatus $phpStatus, CheckStatus $editorStatus): void
    {
        $php = $this->php(ini: ['file_uploads' => '1', 'upload_max_filesize' => $upload, 'post_max_size' => $post]);
        $results = (new UploadLimitsCheck($php, new UploadLimits($php), new EditorImageSettings()))->run();
        self::assertSame($phpStatus, $results[1]->status);
        self::assertSame($editorStatus, $results[2]->status);
    }

    public static function uploadLimits(): iterable
    {
        yield 'editor exceeds PHP' => ['2M', '8M', CheckStatus::Passed, CheckStatus::Failed];
        yield 'form has no room' => ['5M', '5M', CheckStatus::Failed, CheckStatus::Failed];
        yield 'limits fit' => ['8M', '16M', CheckStatus::Passed, CheckStatus::Passed];
        yield 'unlimited POST' => ['8M', '0', CheckStatus::Passed, CheckStatus::Passed];
        yield 'shorthand kilobytes' => ['5120K', '6M', CheckStatus::Passed, CheckStatus::Passed];
    }

    public function testDisabledUploadsSkipDependentRecommendations(): void
    {
        $php = $this->php();
        $results = (new UploadLimitsCheck($php, new UploadLimits($php), new EditorImageSettings()))->run();
        self::assertSame([CheckStatus::Failed, CheckStatus::Skipped, CheckStatus::Skipped], array_column($results, 'status'));
    }

    public function testDevelopmentDebugAndPublicDiagnosticsHaveDifferentSeverity(): void
    {
        self::assertSame(CheckSeverity::Info, (new DebugModeCheck(true, false))->run()[0]->severity);
        self::assertSame(CheckSeverity::Warning, (new DebugModeCheck(true, true))->run()[0]->severity);
        self::assertSame(CheckStatus::Passed, (new DebugModeCheck(false, true))->run()[0]->status);
    }

    /** @param list<string> $missing @param array<string, string> $ini */
    private function php(string $version = '8.4.0', array $missing = [], array $ini = [], string $sapi = 'fpm-fcgi'): PhpEnvironmentInterface
    {
        $php = $this->createMock(PhpEnvironmentInterface::class);
        $php->method('version')->willReturn($version);
        $php->method('extensionLoaded')->willReturnCallback(static fn (string $extension): bool => ! in_array($extension, $missing, true));
        $php->method('ini')->willReturnCallback(static fn (string $name): string => $ini[$name] ?? '');
        $php->method('sapi')->willReturn($sapi);

        return $php;
    }
}
