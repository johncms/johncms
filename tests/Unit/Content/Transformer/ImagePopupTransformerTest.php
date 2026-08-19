<?php

declare(strict_types=1);

namespace Tests\Unit\Content\Transformer;

use Johncms\Content\ContentContext;
use Johncms\Content\Html\HtmlFragment;
use Johncms\Content\Transformer\ImagePopupTransformer;
use PHPUnit\Framework\TestCase;

final class ImagePopupTransformerTest extends TestCase
{
    private HtmlFragment $fragment;

    protected function setUp(): void
    {
        $this->fragment = new HtmlFragment();
    }

    /**
     * The transformer works on a piece of a page. Parsing it used to wrap it in html/body, and
     * writing the document back out put that wrapper into every text that went through here.
     */
    public function testTheResultIsAFragmentWithoutADocumentWrapper(): void
    {
        $result = $this->transform('<p>before</p><figure class="image"><img src="/a.jpg"></figure>');

        self::assertStringNotContainsString('<html>', $result);
        self::assertStringNotContainsString('<body>', $result);
        self::assertStringStartsWith('<p>before</p>', $result);
    }

    public function testAnImageIsWrappedInAPreviewLink(): void
    {
        $document = $this->fragment->parse('<figure class="image"><img src="/a.jpg" alt="A cat"></figure>');
        (new ImagePopupTransformer())->transform($document, new ContentContext());

        $link = $document->querySelector('figure.image a.image-preview');

        self::assertNotNull($link);
        self::assertSame('/a.jpg', $link->getAttribute('href'));
        self::assertSame('/a.jpg', $link->getAttribute('data-source'));
        self::assertSame('A cat', $link->getAttribute('title'));
        self::assertNotNull($link->querySelector('img'));
    }

    /**
     * The link used to be assembled as a string, where a quote in the alt text ended the
     * attribute and let the rest of it into the markup. Built through the DOM it cannot: the
     * value is escaped by the serializer, not by a call somebody has to remember.
     */
    public function testAQuoteInTheAltTextDoesNotBreakOutOfTheAttribute(): void
    {
        $document = $this->fragment->parse(
            '<figure class="image"><img src="/a.jpg" alt="&quot; onmouseover=alert(1) x=&quot;"></figure>'
        );
        (new ImagePopupTransformer())->transform($document, new ContentContext());

        $link = $document->querySelector('figure.image a.image-preview');

        self::assertNotNull($link);
        self::assertSame('" onmouseover=alert(1) x="', $link->getAttribute('title'));
        self::assertNull($link->getAttribute('onmouseover'));
    }

    public function testTextWithoutImagesIsLeftAsItIs(): void
    {
        $text = '<p>Just a paragraph</p>';

        self::assertSame($text, $this->transform($text));
    }

    public function testAnEmptyTextIsReturnedAsItIs(): void
    {
        self::assertSame('', $this->transform(''));
    }

    public function testAFigureWithoutAnImageIsLeftAlone(): void
    {
        $result = $this->transform('<figure class="image"><figcaption>No image</figcaption></figure>');

        self::assertStringContainsString('<figcaption>No image</figcaption>', $result);
        self::assertStringNotContainsString('image-preview', $result);
    }

    /**
     * A link inside a link does not open, and a text can reach the pipeline already carrying one.
     */
    public function testAnImageThatAlreadyStandsInALinkIsNotWrappedAgain(): void
    {
        $result = $this->transform($this->transform('<figure class="image"><img src="/a.jpg"></figure>'));

        self::assertSame(1, substr_count($result, 'image-preview'));
    }

    /**
     * The caption belongs to the figure, not to the link: it is text to read, not a place to
     * click, and it must not end up inside the anchor.
     */
    public function testTheCaptionStaysOutsideTheLink(): void
    {
        $document = $this->fragment->parse(
            '<figure class="image"><img src="/a.jpg"><figcaption>A cat</figcaption></figure>'
        );
        (new ImagePopupTransformer())->transform($document, new ContentContext());

        self::assertNull($document->querySelector('a.image-preview figcaption'));
        self::assertNotNull($document->querySelector('figure.image > figcaption'));
    }

    private function transform(string $html): string
    {
        $document = $this->fragment->parse($html);
        (new ImagePopupTransformer())->transform($document, new ContentContext());

        return $this->fragment->serialize($document);
    }
}
