<?php

declare(strict_types=1);

namespace Johncms\Content\Transformer;

use Dom\HTMLDocument;
use Johncms\Content\ContentContext;

/**
 * An extension point: one step of the content pipeline.
 *
 * A module adds a step by registering a service that implements this interface — the container
 * tags it and the renderer picks it up, so nothing in the core is edited and the step survives
 * an update of the CMS.
 *
 * The document is parsed once and every transformer works on that same tree, so a step costs a
 * walk of the nodes it asks for rather than a parse of the whole text. A transformer therefore
 * never re-parses or serializes: it edits the tree it is given and returns nothing.
 *
 * Markup a transformer builds goes into the tree through the DOM — setAttribute() and
 * HtmlFragment::nodes() escape it. Concatenating a value into an HTML string is how the old
 * providers worked and how an alt text carrying a quote used to break out of its attribute.
 */
interface ContentTransformerInterface
{
    /**
     * The order of the steps: the higher, the earlier. The built-in ones sit at 100 (the media),
     * 50 (the images) and -100 (the smilies, which run over the finished text).
     */
    public function priority(): int;

    public function transform(HTMLDocument $document, ContentContext $context): void;
}
