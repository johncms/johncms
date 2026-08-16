<?php

declare(strict_types=1);

namespace Tests\Unit\Modules\Collections\Application\Services;

use Johncms\Modules\Collections\Application\Services\ItemContentFormatter;
use Johncms\Security\HtmlSanitizerInterface;
use PHPUnit\Framework\TestCase;
use Twig\Markup;

/**
 * What the formatter owes: nothing reaches the template unsanitized, and "no content" is null
 * rather than empty markup. How the sanitizing itself works is covered by HtmlSanitizerTest.
 */
final class ItemContentFormatterTest extends TestCase
{
    public function testReturnsNullForNullOrEmpty(): void
    {
        $sanitizer = $this->createMock(HtmlSanitizerInterface::class);
        // Nothing to sanitize, so nothing is asked of the sanitizer either.
        $sanitizer->expects(self::never())->method('sanitize');

        $formatter = new ItemContentFormatter($sanitizer);

        self::assertNull($formatter->format(null));
        self::assertNull($formatter->format(''));
    }

    public function testSanitizesTheContentBeforeItBecomesMarkup(): void
    {
        $sanitizer = $this->createMock(HtmlSanitizerInterface::class);
        // Item content is written in the editor, so it takes the default rich-content policy.
        $sanitizer->expects(self::once())
            ->method('sanitize')
            ->with('<p>Hello <b>world</b></p><script>alert(1)</script>')
            ->willReturn('<p>Hello <b>world</b></p>');

        $result = (new ItemContentFormatter($sanitizer))->format('<p>Hello <b>world</b></p><script>alert(1)</script>');

        self::assertInstanceOf(Markup::class, $result);
        // Markup by contract, so the assertion reads it as the string it prints as.
        self::assertSame('<p>Hello <b>world</b></p>', (string) $result);
    }
}
