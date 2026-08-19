<?php

declare(strict_types=1);

namespace Johncms\Content\Transformer;

use Dom\Element;
use Dom\HTMLDocument;
use Johncms\Content\ContentContext;

/**
 * Wraps every image of a text in a link that opens it in the viewer.
 *
 * The link is built through the DOM, so the address and the alt text are escaped by the
 * serializer. The version that assembled the tag as a string had to escape them by hand, and
 * that is the kind of markup where a forgotten call is an XSS.
 */
final readonly class ImagePopupTransformer implements ContentTransformerInterface
{
    public function priority(): int
    {
        return 50;
    }

    public function transform(HTMLDocument $document, ContentContext $context): void
    {
        foreach ($document->querySelectorAll('figure.image') as $figure) {
            if (! $figure instanceof Element) {
                continue;
            }

            $image = $figure->querySelector('img');
            // A figure the editor left without a picture — a caption on its own, most often.
            if (! $image instanceof Element) {
                continue;
            }

            // Already wrapped: the text went through the pipeline twice, or the author pasted
            // markup that carries the link. A second link inside the first one would not open.
            if ($image->closest('a') !== null) {
                continue;
            }

            $source = $image->getAttribute('src') ?? '';

            $link = $document->createElement('a');
            $link->setAttribute('class', 'image-preview');
            $link->setAttribute('href', $source);
            $link->setAttribute('data-source', $source);
            $link->setAttribute('title', $image->getAttribute('alt') ?? '');

            $image->replaceWith($link);
            $link->appendChild($image);
        }
    }
}
