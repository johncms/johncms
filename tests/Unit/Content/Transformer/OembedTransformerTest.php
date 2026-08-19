<?php

declare(strict_types=1);

namespace Tests\Unit\Content\Transformer;

use Johncms\Content\ContentContext;
use Johncms\Content\Embed\EmbeddedMedia;
use Johncms\Content\Embed\EmbedProviderInterface;
use Johncms\Content\Embed\EmbedProviderRegistry;
use Johncms\Content\Html\HtmlFragment;
use Johncms\Content\Transformer\OembedTransformer;
use Johncms\View\RendererInterface;
use PHPUnit\Framework\TestCase;

final class OembedTransformerTest extends TestCase
{
    private HtmlFragment $fragment;

    protected function setUp(): void
    {
        $this->fragment = new HtmlFragment();
    }

    public function testTheElementOfTheEditorIsReplacedByThePlayerTheProviderNames(): void
    {
        self::assertSame(
            '<figure class="media"><iframe src="/player/one"></iframe></figure>',
            $this->transform('<figure class="media"><oembed url="https://example.org/one"></oembed></figure>')
        );
    }

    /**
     * A site nobody wrote a provider for is not an error: the link stays in the text, so it is
     * not lost and a provider added later picks the same post up.
     */
    public function testAnAddressNobodyClaimsIsLeftAsItIs(): void
    {
        $html = '<figure class="media"><oembed url="https://unknown.example/x"></oembed></figure>';

        self::assertSame($html, $this->transform($html));
    }

    /**
     * The player takes the place of the element, not of everything around it: the caption the
     * author wrote belongs to the figure and has to survive.
     */
    public function testTheCaptionOfTheFigureSurvives(): void
    {
        self::assertSame(
            '<figure class="media"><iframe src="/player/one"></iframe><figcaption>A talk</figcaption></figure>',
            $this->transform(
                '<figure class="media"><oembed url="https://example.org/one"></oembed>'
                . '<figcaption>A talk</figcaption></figure>'
            )
        );
    }

    public function testEveryEmbedOfATextIsReplaced(): void
    {
        $result = $this->transform(
            '<oembed url="https://example.org/one"></oembed><p>and</p><oembed url="https://example.org/two"></oembed>'
        );

        self::assertSame('<iframe src="/player/one"></iframe><p>and</p><iframe src="/player/two"></iframe>', $result);
    }

    public function testAnElementWithoutAnAddressIsNotAnEmbed(): void
    {
        $html = '<figure class="media"><oembed></oembed></figure>';

        self::assertSame($html, $this->transform($html));
    }

    private function transform(string $html): string
    {
        $document = $this->fragment->parse($html);
        (new OembedTransformer($this->providers(), $this->renderer(), $this->fragment))
            ->transform($document, new ContentContext());

        return $this->fragment->serialize($document);
    }

    private function providers(): EmbedProviderRegistry
    {
        $provider = new class implements EmbedProviderInterface {
            public function priority(): int
            {
                return 0;
            }

            public function embed(string $url): ?EmbeddedMedia
            {
                $host = parse_url($url, PHP_URL_HOST);

                return $host === 'example.org'
                    ? new EmbeddedMedia('@test/player.twig', ['path' => ltrim((string) parse_url($url, PHP_URL_PATH), '/')])
                    : null;
            }
        };

        return new EmbedProviderRegistry([$provider]);
    }

    private function renderer(): RendererInterface
    {
        return new class implements RendererInterface {
            public function render(string $template, array $data = []): string
            {
                return '<iframe src="/player/' . $data['path'] . '"></iframe>';
            }

            public function exists(string $template): bool
            {
                return true;
            }
        };
    }
}
