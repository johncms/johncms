<?php

declare(strict_types=1);

namespace Johncms\Content\Transformer;

use Dom\Element;
use Dom\HTMLDocument;
use Johncms\Content\ContentContext;
use Johncms\Content\Embed\EmbedProviderRegistry;
use Johncms\Content\Html\HtmlFragment;
use Johncms\View\RendererInterface;

/**
 * Replaces the `<oembed url="…">` the editor leaves behind with the player of that address.
 *
 * The editor stores a link and nothing else — the markup of a player is not content and does
 * not belong in the database, where it would freeze the look of every old post. It is built
 * here, on the way out, which is why changing a template changes the posts that already exist.
 */
final readonly class OembedTransformer implements ContentTransformerInterface
{
    public function __construct(
        private EmbedProviderRegistry $providers,
        private RendererInterface $renderer,
        private HtmlFragment $fragment,
    ) {
    }

    public function priority(): int
    {
        return 100;
    }

    public function transform(HTMLDocument $document, ContentContext $context): void
    {
        foreach ($document->querySelectorAll('oembed[url]') as $oembed) {
            if (! $oembed instanceof Element) {
                continue;
            }

            $embed = $this->providers->resolve($oembed->getAttribute('url') ?? '');
            // Nobody claimed the address: the element stays as the editor wrote it, so the link
            // is not lost and a provider added later picks the same post up.
            if ($embed === null) {
                continue;
            }

            $player = $this->renderer->render($embed->template, $embed->parameters);
            $oembed->replaceWith(...$this->fragment->nodes($document, $player));
        }
    }
}
