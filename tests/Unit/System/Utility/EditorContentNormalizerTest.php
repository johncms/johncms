<?php

declare(strict_types=1);

namespace Tests\Unit\System\Utility;

use Johncms\System\Utility\EditorContentNormalizer;
use PHPUnit\Framework\TestCase;

final class EditorContentNormalizerTest extends TestCase
{
    private EditorContentNormalizer $normalizer;

    protected function setUp(): void
    {
        $this->normalizer = new EditorContentNormalizer();
    }

    public function testEmptyStringReturnsEmpty(): void
    {
        self::assertSame('', $this->normalizer->trimEdgeEmptyBlocks(''));
    }

    public function testWhitespaceOnlyReturnsEmpty(): void
    {
        self::assertSame('', $this->normalizer->trimEdgeEmptyBlocks('   '));
    }

    public function testPlainTextIsPreserved(): void
    {
        self::assertSame('Hello world', $this->normalizer->trimEdgeEmptyBlocks('Hello world'));
    }

    public function testTextInParagraphIsPreserved(): void
    {
        self::assertSame('<p>Hello world</p>', $this->normalizer->trimEdgeEmptyBlocks('<p>Hello world</p>'));
    }

    public function testLeadingEmptyParagraphIsStripped(): void
    {
        self::assertSame('<p>Text</p>', $this->normalizer->trimEdgeEmptyBlocks('<p></p><p>Text</p>'));
    }

    public function testTrailingEmptyParagraphIsStripped(): void
    {
        self::assertSame('<p>Text</p>', $this->normalizer->trimEdgeEmptyBlocks('<p>Text</p><p></p>'));
    }

    public function testMultipleLeadingAndTrailingEmptyParagraphsAreStripped(): void
    {
        self::assertSame('<p>Text</p>', $this->normalizer->trimEdgeEmptyBlocks('<p></p><p></p><p>Text</p><p></p><p></p>'));
    }

    public function testEmptyParagraphWithNbspIsStripped(): void
    {
        self::assertSame('', $this->normalizer->trimEdgeEmptyBlocks('<p>&nbsp;</p>'));
    }

    public function testEmptyParagraphWithBrIsStripped(): void
    {
        self::assertSame('', $this->normalizer->trimEdgeEmptyBlocks('<p><br></p>'));
    }

    public function testEmptyDivIsStripped(): void
    {
        self::assertSame('', $this->normalizer->trimEdgeEmptyBlocks('<div></div>'));
    }

    public function testFigureWithNoSrcImgReturnsEmpty(): void
    {
        self::assertSame('', $this->normalizer->trimEdgeEmptyBlocks('<figure class="image"><img></figure>'));
    }

    public function testFigureWithEmptySrcImgReturnsEmpty(): void
    {
        self::assertSame('', $this->normalizer->trimEdgeEmptyBlocks('<figure class="image"><img src=""></figure>'));
    }

    public function testFigureWithValidSrcIsPreserved(): void
    {
        $input = '<figure class="image"><img src="photo.jpg"></figure>';
        self::assertSame($input, $this->normalizer->trimEdgeEmptyBlocks($input));
    }

    public function testFigureWithValidSrcAndTextIsPreserved(): void
    {
        $input = '<figure class="image"><img src="photo.jpg"></figure><p>Caption</p>';
        self::assertSame($input, $this->normalizer->trimEdgeEmptyBlocks($input));
    }

    public function testEmptyParagraphBeforeFigureWithNoSrcReturnsEmpty(): void
    {
        self::assertSame('', $this->normalizer->trimEdgeEmptyBlocks('<p></p><figure class="image"><img></figure>'));
    }

    public function testTextWithFigureNoSrcStripsEmptyFigure(): void
    {
        self::assertSame(
            '<p>Some text</p>',
            $this->normalizer->trimEdgeEmptyBlocks('<p>Some text</p><figure class="image"><img></figure>')
        );
    }
}
