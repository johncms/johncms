<?php

declare(strict_types=1);

namespace Tests\Unit\Security;

use Johncms\Config\ConfigRepository;
use Johncms\Security\HtmlPolicy;
use Johncms\Security\HtmlPurifierFactory;
use Johncms\Security\HtmlSanitizer;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * The sanitizer is the last line between what a visitor typed and what the next visitor's
 * browser executes, so the tests are written as claims about safety rather than about the exact
 * string the current library happens to produce. They must keep passing if HTMLPurifier is
 * replaced with something else.
 *
 * The fixtures are in English except where the point of the test is multibyte text.
 */
final class HtmlSanitizerTest extends TestCase
{
    protected function setUp(): void
    {
        // The shipped list of allowed classes, so the test guards the real configuration.
        ConfigRepository::init(require CONFIG_PATH . 'autoload' . DS . 'htmlpurifier.global.php');
    }

    protected function tearDown(): void
    {
        ConfigRepository::init([]);
    }

    private function sanitizer(): HtmlSanitizer
    {
        return new HtmlSanitizer(new HtmlPurifierFactory());
    }

    // -----------------------------------------------------------------------------------
    // Nothing executable survives, under any policy
    // -----------------------------------------------------------------------------------

    /**
     * @return array<string, array{string, list<string>}>
     */
    public static function dangerousMarkup(): array
    {
        return [
            'script element'          => ['<script>alert(1)</script>', ['<script', 'alert(1)']],
            'script with attributes'  => ['<script type="text/javascript" src="//evil.test/x.js"></script>', ['<script', 'evil.test']],
            'img error handler'       => ['<img src="x" onerror="alert(1)">', ['onerror', 'alert(1)']],
            'click handler'           => ['<a href="/x/" onclick="steal()">t</a>', ['onclick', 'steal()']],
            'mouseover handler'       => ['<div onmouseover="evil()">t</div>', ['onmouseover', 'evil()']],
            'javascript uri'          => ['<a href="javascript:alert(document.cookie)">t</a>', ['javascript:', 'document.cookie']],
            'javascript uri upcased'  => ['<a href="JaVaScRiPt:alert(1)">t</a>', ['javascript:', 'JaVaScRiPt']],
            'entity encoded scheme'   => ['<a href="java&#115;cript:alert(1)">t</a>', ['javascript:', 'alert(1)']],
            'vbscript uri'            => ['<a href="vbscript:msgbox(1)">t</a>', ['vbscript:']],
            'data uri in a link'      => ['<a href="data:text/html;base64,PHNjcmlwdD4=">t</a>', ['data:text/html']],
            'iframe'                  => ['<iframe src="https://evil.test/f"></iframe>', ['<iframe', 'evil.test']],
            'object'                  => ['<object data="evil.swf"></object>', ['<object', 'evil.swf']],
            'embed'                   => ['<embed src="evil.swf">', ['<embed', 'evil.swf']],
            'form and input'          => ['<form action="/steal"><input name="pass" type="password"></form>', ['<form', '<input']],
            'base element'            => ['<base href="https://evil.test/">', ['<base', 'evil.test']],
            'meta refresh'            => ['<meta http-equiv="refresh" content="0;url=https://evil.test/">', ['<meta', 'evil.test']],
            'svg animate handler'     => ['<svg><animate onbegin="alert(1)" attributeName="x"></animate></svg>', ['onbegin', 'alert(1)']],
            'style element'           => ['<style>body{background:url(javascript:alert(1))}</style>', ['<style', 'javascript:']],
            'css expression'          => ['<div style="width:expression(alert(1))">t</div>', ['expression(']],
            'css javascript url'      => ['<div style="background:url(javascript:alert(1))">t</div>', ['javascript:']],
            'behaviour property'      => ['<div style="behavior:url(evil.htc)">t</div>', ['behavior', 'evil.htc']],
            'srcdoc on an iframe'     => ['<iframe srcdoc="&lt;script&gt;alert(1)&lt;/script&gt;"></iframe>', ['srcdoc', '<iframe']],
            'formaction on a button'  => ['<button formaction="javascript:alert(1)">t</button>', ['formaction', 'javascript:']],
            'unclosed script'         => ['<script>alert(1)', ['<script', 'alert(1)']],
            // The leftover of the split tag stays as visible text, which is harmless; what
            // matters is that no script element is reassembled out of it.
            'nested broken script'    => ['<scr<script>ipt>alert(1)</script>', ['<script']],
        ];
    }

    /**
     * @param list<string> $mustNotContain
     */
    #[DataProvider('dangerousMarkup')]
    public function testDangerousMarkupIsRemovedFromRichContent(string $input, array $mustNotContain): void
    {
        $result = $this->sanitizer()->sanitize($input, HtmlPolicy::RichContent);

        foreach ($mustNotContain as $needle) {
            self::assertStringNotContainsStringIgnoringCase($needle, $result, sprintf('"%s" survived the sanitizer', $needle));
        }
    }

    /**
     * The inline policies allow strictly less, so nothing dangerous may pass them either.
     *
     * @param list<string> $mustNotContain
     */
    #[DataProvider('dangerousMarkup')]
    public function testDangerousMarkupIsRemovedFromInlineContent(string $input, array $mustNotContain): void
    {
        foreach ([HtmlPolicy::Inline, HtmlPolicy::InlineWithParagraphs] as $policy) {
            $result = $this->sanitizer()->sanitize($input, $policy);

            foreach ($mustNotContain as $needle) {
                self::assertStringNotContainsStringIgnoringCase($needle, $result, sprintf('"%s" survived %s', $needle, $policy->name));
            }
        }
    }

    // -----------------------------------------------------------------------------------
    // Rich content: what the editor produces has to come back intact
    // -----------------------------------------------------------------------------------

    public function testKeepsTheFormattingOfAPost(): void
    {
        $result = $this->sanitizer()->sanitize(
            '<p>A paragraph with <b>bold</b>, <i>italic</i> and <u>underlined</u> text.</p>'
            . '<ul><li>An item</li></ul><blockquote>A quote</blockquote>'
        );

        self::assertStringContainsString('<b>bold</b>', $result);
        self::assertStringContainsString('<i>italic</i>', $result);
        self::assertStringContainsString('<u>underlined</u>', $result);
        self::assertStringContainsString('<li>An item</li>', $result);
        self::assertStringContainsString('<blockquote>', $result);
    }

    public function testKeepsImagesAndTables(): void
    {
        $result = $this->sanitizer()->sanitize(
            '<img src="/upload/images/photo.jpg" alt="A photo" width="640" height="480">'
            . '<table class="table"><tbody><tr><td colspan="2">cell</td></tr></tbody></table>'
        );

        self::assertStringContainsString('src="/upload/images/photo.jpg"', $result);
        self::assertStringContainsString('alt="A photo"', $result);
        self::assertStringContainsString('<table class="table">', $result);
        self::assertStringContainsString('colspan="2"', $result);
    }

    public function testKeepsTheEditorMediaMarkup(): void
    {
        // figure/figcaption/oembed are added to the definition by hand: CKEditor writes them
        // and the media embedder reads them back after sanitizing.
        $result = $this->sanitizer()->sanitize(
            '<figure class="media"><oembed url="https://www.youtube.com/watch?v=aaaaaaaaaaa"></oembed></figure>'
            . '<figure class="image"><img src="/upload/i.jpg" alt="i"><figcaption>A caption</figcaption></figure>'
        );

        self::assertStringContainsString('<oembed url="https://www.youtube.com/watch?v=aaaaaaaaaaa">', $result);
        self::assertStringContainsString('<figure class="media">', $result);
        self::assertStringContainsString('<figcaption>A caption</figcaption>', $result);
    }

    public function testKeepsOnlyTheClassesTheSiteAllows(): void
    {
        $result = $this->sanitizer()->sanitize('<p class="alert alert-danger stolen-class">t</p>');

        self::assertStringContainsString('alert', $result);
        self::assertStringContainsString('alert-danger', $result);
        self::assertStringNotContainsString('stolen-class', $result);
    }

    public function testKeepsTheCodeBlockOfTheHighlighter(): void
    {
        $result = $this->sanitizer()->sanitize(
            '<pre class="line-numbers language-php"><code>&lt;?php $a = 1;</code></pre>'
        );

        self::assertStringContainsString('line-numbers', $result);
        self::assertStringContainsString('language-php', $result);
        // The sample of code stays text and does not become markup.
        self::assertStringContainsString('&lt;?php', $result);
    }

    public function testMakesABareUrlAlinkInRichContent(): void
    {
        $result = $this->sanitizer()->sanitize('See https://johncms.com/forum/ for details');

        self::assertStringContainsString('<a href="https://johncms.com/forum/">', $result);
    }

    public function testKeepsRelativeLinksAndMailto(): void
    {
        $result = $this->sanitizer()->sanitize('<a href="/forum/topic/12/">t</a><a href="mailto:user@example.test">m</a>');

        self::assertStringContainsString('href="/forum/topic/12/"', $result);
        self::assertStringContainsString('mailto:user', $result);
    }

    public function testKeepsTheTargetOfALinkAndProtectsTheOpener(): void
    {
        $result = $this->sanitizer()->sanitize('<a href="https://example.test/" target="_blank">t</a>');

        self::assertStringContainsString('target="_blank"', $result);
        // A tab opened by target="_blank" can reach back through window.opener without this.
        self::assertStringContainsString('noopener', $result);
    }

    public function testDropsAnUnknownLinkTarget(): void
    {
        $result = $this->sanitizer()->sanitize('<a href="https://example.test/" target="evilframe">t</a>');

        self::assertStringNotContainsString('evilframe', $result);
    }

    // -----------------------------------------------------------------------------------
    // Inline policies
    // -----------------------------------------------------------------------------------

    public function testInlinePolicyDropsBlockElementsButKeepsTheirText(): void
    {
        $result = $this->sanitizer()->sanitize(
            '<p>I agree to the <a href="/consent/1/">terms</a></p><h2>A heading</h2><div>A block</div>',
            HtmlPolicy::Inline
        );

        self::assertStringContainsString('<a href="/consent/1/">terms</a>', $result);
        self::assertStringNotContainsString('<p>', $result);
        self::assertStringNotContainsString('<h2>', $result);
        self::assertStringNotContainsString('<div>', $result);
        // The text of a dropped element is content, not markup: it stays.
        self::assertStringContainsString('A heading', $result);
        self::assertStringContainsString('A block', $result);
    }

    public function testInlinePolicyKeepsInlineFormatting(): void
    {
        $result = $this->sanitizer()->sanitize('<b>b</b><strong>s</strong><i>i</i><em>e</em><u>u</u><br>', HtmlPolicy::Inline);

        foreach (['<b>b</b>', '<strong>s</strong>', '<i>i</i>', '<em>e</em>', '<u>u</u>', '<br'] as $expected) {
            self::assertStringContainsString($expected, $result);
        }
    }

    public function testInlinePolicyDropsImagesAndTables(): void
    {
        $result = $this->sanitizer()->sanitize('<img src="/i.jpg" alt="i"><table><tr><td>c</td></tr></table>', HtmlPolicy::Inline);

        self::assertStringNotContainsString('<img', $result);
        self::assertStringNotContainsString('<table', $result);
    }

    public function testInlinePolicyDoesNotLinkifyBareUrls(): void
    {
        // A consent title is a short label; turning a URL in it into a link is not wanted.
        $result = $this->sanitizer()->sanitize('See https://johncms.com/ for details', HtmlPolicy::Inline);

        self::assertStringNotContainsString('<a', $result);
        self::assertStringContainsString('https://johncms.com/', $result);
    }

    public function testInlinePolicyOnlyAllowsTheBlankTarget(): void
    {
        $sanitizer = $this->sanitizer();

        self::assertStringContainsString(
            'target="_blank"',
            $sanitizer->sanitize('<a href="/x/" target="_blank">t</a>', HtmlPolicy::Inline)
        );
        self::assertStringNotContainsString(
            'target=',
            $sanitizer->sanitize('<a href="/x/" target="_top">t</a>', HtmlPolicy::Inline)
        );
    }

    public function testInlineWithParagraphsKeepsParagraphsAndSpans(): void
    {
        $result = $this->sanitizer()->sanitize(
            '<p>We use cookies.</p><span>Read more</span><div>A block</div>',
            HtmlPolicy::InlineWithParagraphs
        );

        self::assertStringContainsString('<p>We use cookies.</p>', $result);
        self::assertStringContainsString('<span>Read more</span>', $result);
        self::assertStringNotContainsString('<div>', $result);
    }

    // -----------------------------------------------------------------------------------
    // Plain text
    // -----------------------------------------------------------------------------------

    public function testPlainTextStripsMarkupDecodesEntitiesAndCollapsesWhitespace(): void
    {
        $result = $this->sanitizer()->toPlainText("  <p>First   paragraph</p>\n\n<p>Second &amp; third</p>  ");

        self::assertSame('First paragraph Second & third', $result);
    }

    public function testPlainTextDoesNotLeakTheContentOfARemovedScript(): void
    {
        // Stripping the tags before sanitizing would turn the body of a script into visible text.
        $result = $this->sanitizer()->toPlainText('Hello<script>alert("stolen")</script>');

        self::assertSame('Hello', $result);
    }

    public function testPlainTextKeepsTheTextOfALink(): void
    {
        $result = $this->sanitizer()->toPlainText('<a href="/x/">the text of the link</a>');

        self::assertSame('the text of the link', $result);
    }

    // -----------------------------------------------------------------------------------
    // General contract
    // -----------------------------------------------------------------------------------

    public function testEmptyInputStaysEmpty(): void
    {
        self::assertSame('', $this->sanitizer()->sanitize(''));
        self::assertSame('', $this->sanitizer()->toPlainText(''));
    }

    public function testSanitizingTwiceChangesNothing(): void
    {
        // Texts are sanitized on output, and a preview may re-sanitize what was sanitized
        // already; the second pass must not eat entities or double-escape anything.
        $sanitizer = $this->sanitizer();
        $once = $sanitizer->sanitize('<p class="alert">A &amp; B <b>bold</b> https://johncms.com/</p>');
        $twice = $sanitizer->sanitize($once);

        self::assertSame($once, $twice);
    }

    public function testRepairsBrokenNesting(): void
    {
        $result = $this->sanitizer()->sanitize('<p>a paragraph <b>bold <i>italic</p> tail');

        self::assertStringContainsString('</i>', $result);
        self::assertStringContainsString('</b>', $result);
        self::assertStringContainsString('tail', $result);
    }

    public function testTurnsTheLeftoverOfASplitTagIntoInertText(): void
    {
        // A tag broken in half must not come back as markup on the page: what is left of it is
        // printed as text, with the angle bracket escaped.
        $result = $this->sanitizer()->sanitize('<scr<script>ipt>alert(1)</script>');

        self::assertStringNotContainsString('<script', $result);
        self::assertStringNotContainsString('ipt>', $result);
        self::assertStringContainsString('ipt&gt;', $result);
    }

    // -----------------------------------------------------------------------------------
    // Multibyte text: the fixtures below are non-ASCII because that is what they test
    // -----------------------------------------------------------------------------------

    /**
     * @return array<string, array{string}>
     */
    public static function multibyteText(): array
    {
        return [
            'cyrillic'   => ['Привет, мир'],
            'greek'      => ['Γειά σου κόσμε'],
            'chinese'    => ['你好世界'],
            'arabic'     => ['مرحبا بالعالم'],
            'emoji'      => ['Hello 👋 world 🌍'],
            // A combining tilde: one grapheme made of two code points.
            'combining'  => ["Man\u{0303}ana"],
        ];
    }

    #[DataProvider('multibyteText')]
    public function testKeepsMultibyteTextByteForByte(string $text): void
    {
        $result = $this->sanitizer()->sanitize('<p><b>' . $text . '</b></p>');

        self::assertStringContainsString($text, $result);
    }

    #[DataProvider('multibyteText')]
    public function testKeepsMultibyteTextInPlainText(string $text): void
    {
        self::assertSame($text, $this->sanitizer()->toPlainText('<p>' . $text . '</p>'));
    }

    public function testKeepsALongMultibyteArticleWhole(): void
    {
        // A sanitizer that limits its input by a byte count would cut a multibyte sequence in
        // half here and silently truncate the article, or drop it altogether.
        $paragraph = '<p>Длинный абзац статьи, который должен дойти целиком.</p>';
        $article = str_repeat($paragraph, 500);

        $result = $this->sanitizer()->sanitize($article);

        self::assertSame(500, substr_count($result, '<p>'));
        self::assertStringContainsString('должен дойти целиком', $result);
    }
}
