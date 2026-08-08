<?php

declare(strict_types=1);

namespace Tests\Unit\Media\Providers;

use DiDom\Document;
use Johncms\Media\Providers\ImagesPopup;
use PHPUnit\Framework\TestCase;

final class ImagesPopupTest extends TestCase
{
    /**
     * The provider works on a fragment of a page. Parsing it wraps it in html/body, and dumping
     * the document as is used to put that wrapper into every text that went through here.
     */
    public function testTheResultIsAFragmentWithoutADocumentWrapper(): void
    {
        $result = (new ImagesPopup())->parse('<p>before</p><figure class="image"><img src="/a.jpg"></figure>');

        self::assertStringNotContainsString('<html>', $result);
        self::assertStringNotContainsString('<body>', $result);
        self::assertStringStartsWith('<p>before</p>', $result);
    }

    public function testAnImageIsWrappedInAPreviewLink(): void
    {
        $result = (new ImagesPopup())->parse('<figure class="image"><img src="/a.jpg" alt="A cat"></figure>');

        $link = (new Document($result))->first('figure.image a.image-preview');

        self::assertNotNull($link);
        self::assertSame('/a.jpg', $link->getAttribute('href'));
        self::assertSame('/a.jpg', $link->getAttribute('data-source'));
        self::assertSame('A cat', $link->getAttribute('title'));
        self::assertNotNull($link->first('img'));
    }

    /**
     * The link is assembled by hand, so a quote in the alt text would end the attribute and let
     * the rest of it into the markup.
     */
    public function testAQuoteInTheAltTextDoesNotBreakOutOfTheAttribute(): void
    {
        $result = (new ImagesPopup())->parse(
            '<figure class="image"><img src="/a.jpg" alt="&quot; onmouseover=alert(1) x=&quot;"></figure>'
        );

        $link = (new Document($result))->first('figure.image a.image-preview');

        self::assertNotNull($link);
        self::assertSame('" onmouseover=alert(1) x="', $link->getAttribute('title'));
        self::assertNull($link->getAttribute('onmouseover'));
    }

    public function testTextWithoutImagesIsLeftAsItIs(): void
    {
        $text = '<p>Just a paragraph</p>';

        self::assertSame($text, (new ImagesPopup())->parse($text));
    }

    /**
     * DOMDocument raises a ValueError on an empty string — not an exception, so it would not be
     * caught and would take the page down.
     */
    public function testAnEmptyTextIsReturnedAsItIs(): void
    {
        self::assertSame('', (new ImagesPopup())->parse(''));
        self::assertSame('   ', (new ImagesPopup())->parse('   '));
    }

    public function testAFigureWithoutAnImageIsLeftAlone(): void
    {
        $result = (new ImagesPopup())->parse('<figure class="image"><figcaption>No image</figcaption></figure>');

        self::assertStringContainsString('<figcaption>No image</figcaption>', $result);
        self::assertStringNotContainsString('image-preview', $result);
    }
}
