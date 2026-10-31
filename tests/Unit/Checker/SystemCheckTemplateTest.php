<?php

declare(strict_types=1);

namespace Tests\Unit\Checker;

use Johncms\Checker\CheckSeverity;
use Johncms\Checker\CheckStatus;
use Johncms\Checker\DTO\CheckReportDTO;
use Johncms\Checker\DTO\CheckResultDTO;
use PHPUnit\Framework\TestCase;
use Twig\Environment;
use Twig\Loader\ArrayLoader;
use Twig\Loader\ChainLoader;
use Twig\Loader\FilesystemLoader;
use Twig\TwigFunction;

final class SystemCheckTemplateTest extends TestCase
{
    public function testAnIncompleteReportCannotShowASuccessSummary(): void
    {
        $html = $this->render([new CheckResultDTO('unknown', 'Unknown', '', CheckStatus::Unavailable, CheckSeverity::Warning, 'Try again')]);
        self::assertStringContainsString('data-check-summary="unavailable"', $html);
        self::assertStringNotContainsString('data-check-summary="ok"', $html);
    }

    public function testAnEmptyReportCannotShowASuccessSummary(): void
    {
        self::assertStringContainsString('data-check-summary="unavailable"', $this->render([]));
    }

    public function testSkippedChecksDoNotHideAHealthyCompletedCheck(): void
    {
        $html = $this->render([
            new CheckResultDTO('ok', 'OK', '', CheckStatus::Passed, CheckSeverity::Critical, ''),
            new CheckResultDTO('skipped', 'Skipped', '', CheckStatus::Skipped, CheckSeverity::Critical, ''),
        ]);
        self::assertStringContainsString('data-check-summary="ok"', $html);
    }

    public function testNamesValuesAndDescriptionsAreEscapedAndDocumentationIsALink(): void
    {
        $html = $this->render([new CheckResultDTO(
            'escaped',
            '<b>Name</b>',
            '<script>value</script>',
            CheckStatus::Passed,
            CheckSeverity::Critical,
            '<img src=x onerror=alert(1)>',
            'https://example.com/manual?a=1&b=2',
        )]);
        self::assertStringContainsString('&lt;b&gt;Name&lt;/b&gt;', $html);
        self::assertStringContainsString('&lt;script&gt;value&lt;/script&gt;', $html);
        self::assertStringContainsString('&lt;img', $html);
        self::assertStringContainsString('href="https://example.com/manual?a=1&amp;b=2"', $html);
        self::assertStringNotContainsString('<script>', $html);
    }

    /** @param list<CheckResultDTO> $checks */
    private function render(array $checks): string
    {
        $files = new FilesystemLoader();
        $files->addPath(ROOT_PATH . 'modules/johncms/admin/templates', 'admin');
        $twig = new Environment(new ChainLoader([
            new ArrayLoader([
                '@admin/layouts/default.twig' => '{% block content %}{% endblock %}',
                '@admin/components/ui/icon.twig' => '',
            ]),
            $files,
        ]), ['autoescape' => 'html', 'strict_variables' => true]);
        $twig->addFunction(new TwigFunction('__', static fn (string $text, mixed ...$args): string => sprintf($text, ...$args)));
        $report = new CheckReportDTO($checks === [] ? [] : [['title' => 'Test', 'icon' => 'gauge', 'checks' => $checks]], 123, 'test');

        return $twig->render('@admin/system-check.twig', [
            'report' => $report, 'sections' => $report->sections, 'checked_at' => 'now', 'app' => ['csrf_token' => 'token'],
        ]);
    }
}
