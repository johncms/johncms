<?php

declare(strict_types=1);

namespace Tests\Unit\Modules\Collections\Application\Services;

use Johncms\Content\ContentRendererInterface;
use Johncms\Modules\Collections\Application\Services\ItemContentFormatter;
use PHPUnit\Framework\TestCase;
use Twig\Markup;

final class ItemContentFormatterTest extends TestCase
{
    public function testReturnsNullForNullOrEmpty(): void
    {
        $renderer = $this->createMock(ContentRendererInterface::class);
        $renderer->expects(self::never())->method('renderOrNull');
        $formatter = new ItemContentFormatter($renderer);
        self::assertNull($formatter->format(null));
        self::assertNull($formatter->format(''));
    }

    public function testDelegatesHtmlAndPlainTextToTheSharedPipeline(): void
    {
        $renderer = $this->createMock(ContentRendererInterface::class);
        $markup = new Markup('<p>Hello <b>world</b></p>', 'UTF-8');
        $source = '<p>Hello <b>world</b></p><script>alert(1)</script>';
        $renderer->expects(self::once())->method('renderOrNull')->with($source)->willReturn($markup);
        $renderer->expects(self::once())->method('toPlainText')->with($source)->willReturn('Hello world');
        $formatter = new ItemContentFormatter($renderer);
        self::assertSame($markup, $formatter->format($source));
        self::assertSame('Hello world', $formatter->plain($source));
        self::assertSame('', $formatter->plain(null));
    }
}
