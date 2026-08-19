<?php

declare(strict_types=1);

namespace Tests\Unit\Content\Transformer;

use Johncms\Content\ContentContext;
use Johncms\Content\Html\HtmlFragment;
use Johncms\Content\Transformer\SmiliesTransformer;
use Johncms\Smilies\SmiliesRendererInterface;
use PHPUnit\Framework\TestCase;

final class SmiliesTransformerTest extends TestCase
{
    private HtmlFragment $fragment;

    protected function setUp(): void
    {
        $this->fragment = new HtmlFragment();
    }

    public function testACodeInTheTextBecomesTheImageOfTheSmiley(): void
    {
        self::assertSame(
            '<p>hello <img src="/s/smile.gif" alt=":)"> world</p>',
            $this->transform('<p>hello :) world</p>')
        );
    }

    /**
     * The replacement used to run over the HTML of the whole post, so a code that happened to
     * stand in the value of an attribute — a title, an address — was replaced there as well and
     * broke the markup around it.
     */
    public function testACodeInsideAnAttributeIsLeftAlone(): void
    {
        self::assertSame(
            '<p><a href="/x?q=:)" title="see :)">link <img src="/s/smile.gif" alt=":)"></a></p>',
            $this->transform('<p><a href="/x?q=:)" title="see :)">link :)</a></p>')
        );
    }

    /**
     * Inside these the characters themselves are the point: the author is showing them.
     */
    public function testACodeInsideCodeOrPreIsLeftAlone(): void
    {
        self::assertSame(
            '<pre>:)</pre><code>:)</code>',
            $this->transform('<pre>:)</pre><code>:)</code>')
        );
    }

    public function testTheTextAroundASmileyKeepsItsEscaping(): void
    {
        self::assertSame(
            '<p>5 &lt; 6 &amp; <img src="/s/smile.gif" alt=":)"></p>',
            $this->transform('<p>5 &lt; 6 &amp; :)</p>')
        );
    }

    public function testTheSmiliesOfTheStaffOnlyRenderForTheStaff(): void
    {
        self::assertSame('<p>:boss:</p>', $this->transform('<p>:boss:</p>'));
        self::assertSame(
            '<p><img src="/s/boss.gif" alt=":boss:"></p>',
            $this->transform('<p>:boss:</p>', adminSmilies: true)
        );
    }

    public function testATextWithoutASmileyIsNotTouched(): void
    {
        self::assertSame('<p>nothing here</p>', $this->transform('<p>nothing here</p>'));
    }

    public function testAnInstallationWithoutSmiliesRendersTheTextAsItIs(): void
    {
        $document = $this->fragment->parse('<p>hello :) world</p>');
        (new SmiliesTransformer($this->smilies(withMap: false), $this->fragment))
            ->transform($document, new ContentContext());

        self::assertSame('<p>hello :) world</p>', $this->fragment->serialize($document));
    }

    private function transform(string $html, bool $adminSmilies = false): string
    {
        $document = $this->fragment->parse($html);
        (new SmiliesTransformer($this->smilies(), $this->fragment))
            ->transform($document, new ContentContext(adminSmilies: $adminSmilies));

        return $this->fragment->serialize($document);
    }

    private function smilies(bool $withMap = true): SmiliesRendererInterface
    {
        return new class ($withMap) implements SmiliesRendererInterface {
            public function __construct(private readonly bool $withMap)
            {
            }

            public function render(string $text, bool $withAdminSmilies = false): string
            {
                return strtr($text, $this->map($withAdminSmilies));
            }

            public function map(bool $withAdminSmilies = false): array
            {
                if (! $this->withMap) {
                    return [];
                }

                $map = [':)' => '<img src="/s/smile.gif" alt=":)">'];
                if ($withAdminSmilies) {
                    $map[':boss:'] = '<img src="/s/boss.gif" alt=":boss:">';
                }

                return $map;
            }
        };
    }
}
