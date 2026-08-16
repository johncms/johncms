<?php

declare(strict_types=1);

namespace Tests\Unit\Modules\Consent\Application\Services;

use Johncms\Config\ConfigRepository;
use Johncms\Modules\Consent\Application\Services\ConsentTitleFormatter;
use Johncms\Security\HtmlPolicy;
use Johncms\Security\HtmlPurifierFactory;
use Johncms\Security\HtmlSanitizer;
use Johncms\Security\HtmlSanitizerInterface;
use PHPUnit\Framework\TestCase;

/**
 * A consent title sits inside a form label, next to the checkbox, so it may carry a link but
 * never a block element that would break the line it lives on.
 */
final class ConsentTitleFormatterTest extends TestCase
{
    protected function setUp(): void
    {
        ConfigRepository::init(require CONFIG_PATH . 'autoload' . DS . 'htmlpurifier.global.php');
    }

    protected function tearDown(): void
    {
        ConfigRepository::init([]);
    }

    private function withRealSanitizer(): ConsentTitleFormatter
    {
        return new ConsentTitleFormatter(new HtmlSanitizer(new HtmlPurifierFactory()));
    }

    public function testAsksForTheInlinePolicy(): void
    {
        $sanitizer = $this->createMock(HtmlSanitizerInterface::class);
        $sanitizer->expects(self::once())
            ->method('sanitize')
            ->with('title', HtmlPolicy::Inline)
            ->willReturn('sanitized');

        self::assertSame('sanitized', (new ConsentTitleFormatter($sanitizer))->toHtml('title'));
    }

    public function testPlainTextAsksForTheInlinePolicyToo(): void
    {
        $sanitizer = $this->createMock(HtmlSanitizerInterface::class);
        $sanitizer->expects(self::once())
            ->method('toPlainText')
            ->with('title', HtmlPolicy::Inline)
            ->willReturn('plain');

        self::assertSame('plain', (new ConsentTitleFormatter($sanitizer))->toPlainText('title'));
    }

    public function testKeepsTheLinkOfATitleAndDropsTheBlockAroundIt(): void
    {
        $result = $this->withRealSanitizer()->toHtml(
            '<div>I accept the <a href="/consent/1/" target="_blank">terms</a></div><script>x</script>'
        );

        self::assertStringContainsString('<a href="/consent/1/"', $result);
        self::assertStringContainsString('terms', $result);
        self::assertStringNotContainsString('<div>', $result);
        self::assertStringNotContainsString('<script', $result);
    }

    public function testPlainTextIsWhatGoesIntoABreadcrumb(): void
    {
        self::assertSame(
            'I accept the terms',
            $this->withRealSanitizer()->toPlainText('<b>I accept</b> the <a href="/consent/1/">terms</a>')
        );
    }

    public function testPlainTextDropsMarkupTheTitleWasNeverAllowedToCarry(): void
    {
        // The title is sanitized before its tags are stripped, so the body of a script cannot
        // end up as visible text in a breadcrumb.
        self::assertSame('Terms', $this->withRealSanitizer()->toPlainText('Terms<script>alert("x")</script>'));
    }
}
