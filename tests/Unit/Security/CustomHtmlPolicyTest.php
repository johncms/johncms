<?php

declare(strict_types=1);

namespace Tests\Unit\Security;

use InvalidArgumentException;
use Johncms\Config\ConfigRepository;
use Johncms\Security\HtmlPolicy;
use Johncms\Security\HtmlPolicyDefinition;
use Johncms\Security\HtmlPolicyProviderInterface;
use Johncms\Security\HtmlPolicyRegistry;
use Johncms\Security\HtmlPurifierFactory;
use Johncms\Security\HtmlSanitizer;
use Johncms\Security\UnknownHtmlPolicyException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use RuntimeException;

/**
 * A policy declared by a module: it must be able to describe its own content without naming a
 * library, and it must not be able to describe something unsafe.
 */
final class CustomHtmlPolicyTest extends TestCase
{
    protected function setUp(): void
    {
        ConfigRepository::init(require CONFIG_PATH . 'autoload' . DS . 'htmlpurifier.global.php');
    }

    protected function tearDown(): void
    {
        ConfigRepository::init([]);
    }

    private function sanitizerWith(HtmlPolicyDefinition ...$definitions): HtmlSanitizer
    {
        return new HtmlSanitizer(
            new HtmlPurifierFactory(),
            new HtmlPolicyRegistry([self::provider(...$definitions)])
        );
    }

    private static function provider(HtmlPolicyDefinition ...$definitions): HtmlPolicyProviderInterface
    {
        return new class ($definitions) implements HtmlPolicyProviderInterface {
            /** @param list<HtmlPolicyDefinition> $definitions */
            public function __construct(private readonly array $definitions)
            {
            }

            public function policies(): iterable
            {
                return $this->definitions;
            }
        };
    }

    private static function signaturePolicy(): HtmlPolicyDefinition
    {
        return new HtmlPolicyDefinition(
            name: 'my-module.signature',
            elements: [
                'a'  => ['href', 'title', 'target', 'rel'],
                'b'  => [],
                'br' => [],
                'span' => ['class'],
            ],
            allowedClasses: ['signature'],
        );
    }

    // -----------------------------------------------------------------------------------
    // A module's policy does what it declared
    // -----------------------------------------------------------------------------------

    public function testTheDeclaredElementsSurvive(): void
    {
        $result = $this->sanitizerWith(self::signaturePolicy())->sanitize(
            '<b>bold</b><br><a href="/x/" title="t">link</a>',
            'my-module.signature'
        );

        self::assertStringContainsString('<b>bold</b>', $result);
        self::assertStringContainsString('<br', $result);
        self::assertStringContainsString('<a href="/x/" title="t">link</a>', $result);
    }

    public function testAnythingNotDeclaredIsDropped(): void
    {
        $result = $this->sanitizerWith(self::signaturePolicy())->sanitize(
            '<p>paragraph</p><i>italic</i><img src="/i.jpg" alt="i"><table><tr><td>c</td></tr></table>',
            'my-module.signature'
        );

        foreach (['<p>', '<i>', '<img', '<table'] as $notAllowed) {
            self::assertStringNotContainsString($notAllowed, $result);
        }
        // The text of a dropped element is content, so it stays.
        self::assertStringContainsString('paragraph', $result);
        self::assertStringContainsString('italic', $result);
    }

    public function testAnAttributeThatWasNotDeclaredIsDropped(): void
    {
        $result = $this->sanitizerWith(self::signaturePolicy())->sanitize(
            '<a href="/x/" id="anchor" style="color:red">link</a>',
            'my-module.signature'
        );

        self::assertStringContainsString('href="/x/"', $result);
        self::assertStringNotContainsString('id=', $result);
        self::assertStringNotContainsString('style=', $result);
    }

    public function testTheClassListOfThePolicyApplies(): void
    {
        $result = $this->sanitizerWith(self::signaturePolicy())->sanitize(
            '<span class="signature alert">s</span>',
            'my-module.signature'
        );

        self::assertStringContainsString('signature', $result);
        // "alert" belongs to the rich-content policy of the site, not to this one.
        self::assertStringNotContainsString('alert', $result);
    }

    public function testLinkifyIsOffUnlessThePolicyAsksForIt(): void
    {
        $off = $this->sanitizerWith(self::signaturePolicy())
            ->sanitize('see https://johncms.com/ here', 'my-module.signature');
        self::assertStringNotContainsString('<a', $off);

        $on = $this->sanitizerWith(new HtmlPolicyDefinition(
            name: 'my-module.linkified',
            elements: ['a' => ['href']],
            linkify: true,
        ))->sanitize('see https://johncms.com/ here', 'my-module.linkified');
        self::assertStringContainsString('<a href="https://johncms.com/"', $on);
    }

    public function testOnlyTheDeclaredLinkSchemesSurvive(): void
    {
        $sanitizer = $this->sanitizerWith(new HtmlPolicyDefinition(
            name: 'my-module.https-only',
            elements: ['a' => ['href']],
            linkSchemes: ['https'],
        ));

        self::assertStringContainsString(
            'href="https://example.test/"',
            $sanitizer->sanitize('<a href="https://example.test/">t</a>', 'my-module.https-only')
        );
        self::assertStringNotContainsString(
            'http://example.test',
            $sanitizer->sanitize('<a href="http://example.test/">t</a>', 'my-module.https-only')
        );
        self::assertStringNotContainsString(
            'mailto:',
            $sanitizer->sanitize('<a href="mailto:user@example.test">t</a>', 'my-module.https-only')
        );
    }

    /**
     * The schemes of one policy must not widen another.
     *
     * The library keeps its scheme validators in a registry shared by the whole process and, left
     * to its defaults, hands out one that is already there without checking it against the policy
     * being applied. A single permissive policy anywhere in the request would then decide for
     * every policy built after it — and which policy that is depends on the order things happen
     * to run in, which is why this went unnoticed.
     */
    public function testAPermissivePolicyDoesNotWidenALaterOne(): void
    {
        $permissive = $this->sanitizerWith(new HtmlPolicyDefinition(
            name: 'my-module.any-link',
            elements: ['a' => ['href']],
            linkSchemes: ['http', 'https', 'mailto'],
        ));
        $permissive->sanitize('<a href="http://example.test/">t</a>', 'my-module.any-link');

        $strict = $this->sanitizerWith(new HtmlPolicyDefinition(
            name: 'my-module.https-only-after',
            elements: ['a' => ['href']],
            linkSchemes: ['https'],
        ));

        self::assertStringNotContainsString(
            'http://example.test',
            $strict->sanitize('<a href="http://example.test/">t</a>', 'my-module.https-only-after')
        );
        self::assertStringNotContainsString(
            'mailto:',
            $strict->sanitize('<a href="mailto:user@example.test">t</a>', 'my-module.https-only-after')
        );
    }

    public function testAnEmptyPolicyStripsEveryTag(): void
    {
        $result = $this->sanitizerWith(new HtmlPolicyDefinition(name: 'my-module.text', elements: []))
            ->sanitize('<b>bold</b> and <a href="/x/">link</a>', 'my-module.text');

        self::assertStringNotContainsString('<', $result);
        self::assertStringContainsString('bold', $result);
        self::assertStringContainsString('link', $result);
    }

    // -----------------------------------------------------------------------------------
    // A module cannot declare something unsafe
    // -----------------------------------------------------------------------------------

    /**
     * @return array<string, array{array<string, list<string>>}>
     */
    public static function unsafeElements(): array
    {
        return [
            'script'   => [['script' => ['src']]],
            'iframe'   => [['iframe' => ['src']]],
            'object'   => [['object' => ['data']]],
            'embed'    => [['embed' => ['src']]],
            'form'     => [['form' => ['action']]],
            'input'    => [['input' => ['name']]],
            'style'    => [['style' => []]],
            'base'     => [['base' => ['href']]],
            'meta'     => [['meta' => ['http-equiv']]],
            'svg'      => [['svg' => []]],
            'upper case' => [['SCRIPT' => []]],
        ];
    }

    /**
     * @param array<string, list<string>> $elements
     */
    #[DataProvider('unsafeElements')]
    public function testAnElementThatCarriesBehaviourCannotBeDeclared(array $elements): void
    {
        // Refused where it is written, not quietly dropped at render time: the author of the
        // module finds out about it from the error, not from a security report.
        $this->expectException(InvalidArgumentException::class);

        new HtmlPolicyDefinition(name: 'my-module.reckless', elements: $elements);
    }

    public function testAnEventHandlerCannotBeDeclared(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('event handler');

        new HtmlPolicyDefinition(name: 'my-module.reckless', elements: ['b' => ['onclick']]);
    }

    public function testAnExecutableSchemeCannotBeDeclared(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('javascript');

        new HtmlPolicyDefinition(
            name: 'my-module.reckless',
            elements: ['a' => ['href']],
            linkSchemes: ['http', 'javascript'],
        );
    }

    /**
     * @return array<string, array{string}>
     */
    public static function dangerousMarkup(): array
    {
        return [
            'script'          => ['<script>alert(1)</script>'],
            'event handler'   => ['<b onclick="alert(1)">t</b>'],
            'javascript uri'  => ['<a href="javascript:alert(1)">t</a>'],
            'iframe'          => ['<iframe src="https://evil.test/"></iframe>'],
            'form'            => ['<form action="/steal"><input name="p"></form>'],
            'img handler'     => ['<img src="x" onerror="alert(1)">'],
        ];
    }

    /**
     * The widest policy a module is allowed to declare still lets none of this through.
     */
    #[DataProvider('dangerousMarkup')]
    public function testTheWidestAllowedPolicyStillStopsTheUsualVectors(string $input): void
    {
        $wide = new HtmlPolicyDefinition(
            name: 'my-module.wide',
            elements: [
                'a' => ['href', 'title', 'target', 'rel'],
                'b' => [], 'i' => [], 'u' => [], 'em' => [], 'strong' => [],
                'p' => ['class'], 'span' => ['class'], 'br' => [],
                'img' => ['src', 'alt'],
            ],
            allowedClasses: null,
            linkify: true,
        );

        $result = $this->sanitizerWith($wide)->sanitize($input, 'my-module.wide');

        foreach (['<script', '<iframe', '<form', '<input', 'onclick', 'onerror', 'javascript:', 'alert(1)'] as $needle) {
            self::assertStringNotContainsStringIgnoringCase($needle, $result, sprintf('"%s" survived', $needle));
        }
    }

    // -----------------------------------------------------------------------------------
    // The registry
    // -----------------------------------------------------------------------------------

    public function testAskingForAPolicyNobodyDeclaredFails(): void
    {
        $this->expectException(UnknownHtmlPolicyException::class);
        // The message names what is available, so a typo is obvious from the error alone.
        $this->expectExceptionMessage('my-module.signature');

        $this->sanitizerWith(self::signaturePolicy())->sanitize('<b>t</b>', 'my-module.typo');
    }

    public function testTwoProvidersCannotClaimTheSameName(): void
    {
        $registry = new HtmlPolicyRegistry([
            self::provider(self::signaturePolicy()),
            self::provider(self::signaturePolicy()),
        ]);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('declared twice');

        $registry->get('my-module.signature');
    }

    public function testTheRegistryListsWhatWasDeclared(): void
    {
        $registry = new HtmlPolicyRegistry([self::provider(self::signaturePolicy())]);

        self::assertTrue($registry->has('my-module.signature'));
        self::assertFalse($registry->has('my-module.other'));
        self::assertSame(['my-module.signature'], $registry->names());
    }

    public function testAPolicyNeedsAName(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new HtmlPolicyDefinition(name: '  ', elements: []);
    }

    // -----------------------------------------------------------------------------------
    // The built-in policies keep working next to the declared ones
    // -----------------------------------------------------------------------------------

    public function testABuiltInPolicyIsNotReachableByName(): void
    {
        // The enum is the only way to the built-in policies; a module naming its own policy
        // "RichContent" must not silently take over the content of the whole site.
        $this->expectException(UnknownHtmlPolicyException::class);

        $this->sanitizerWith(self::signaturePolicy())->sanitize('<p>t</p>', 'RichContent');
    }

    public function testTheBuiltInPoliciesStillWork(): void
    {
        $sanitizer = $this->sanitizerWith(self::signaturePolicy());

        self::assertStringContainsString(
            '<p class="alert">t</p>',
            $sanitizer->sanitize('<p class="alert evil">t</p>', HtmlPolicy::RichContent)
        );
        self::assertStringContainsString(
            'signature',
            $sanitizer->sanitize('<span class="signature">s</span>', 'my-module.signature')
        );
    }
}
