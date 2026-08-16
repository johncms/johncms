<?php

declare(strict_types=1);

namespace Tests\Unit\Modules\Consent\Application\Services;

use Johncms\Config\ConfigRepository;
use Johncms\Modules\Consent\Application\Services\CookieBannerTextFormatter;
use Johncms\Security\HtmlPolicy;
use Johncms\Security\HtmlPurifierFactory;
use Johncms\Security\HtmlSanitizer;
use Johncms\Security\HtmlSanitizerInterface;
use PHPUnit\Framework\TestCase;

/**
 * The banner text is a short block of its own, so unlike a consent title it may carry
 * paragraphs — but still nothing that lays out a page.
 */
final class CookieBannerTextFormatterTest extends TestCase
{
    protected function setUp(): void
    {
        ConfigRepository::init(require CONFIG_PATH . 'autoload' . DS . 'htmlpurifier.global.php');
    }

    protected function tearDown(): void
    {
        ConfigRepository::init([]);
    }

    public function testAsksForTheInlineWithParagraphsPolicy(): void
    {
        $sanitizer = $this->createMock(HtmlSanitizerInterface::class);
        $sanitizer->expects(self::once())
            ->method('sanitize')
            ->with('text', HtmlPolicy::InlineWithParagraphs)
            ->willReturn('sanitized');

        self::assertSame('sanitized', (new CookieBannerTextFormatter($sanitizer))->toHtml('text'));
    }

    public function testKeepsParagraphsAndLinksButNotTheLayout(): void
    {
        $formatter = new CookieBannerTextFormatter(new HtmlSanitizer(new HtmlPurifierFactory()));

        $result = $formatter->toHtml(
            '<p>We use cookies. <a href="/consent/1/">Read more</a></p><div class="row">layout</div><script>x</script>'
        );

        self::assertStringContainsString('<p>We use cookies.', $result);
        self::assertStringContainsString('<a href="/consent/1/">Read more</a>', $result);
        self::assertStringNotContainsString('<div', $result);
        self::assertStringNotContainsString('<script', $result);
    }
}
