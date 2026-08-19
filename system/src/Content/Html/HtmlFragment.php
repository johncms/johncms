<?php

declare(strict_types=1);

namespace Johncms\Content\Html;

use Dom\HTMLDocument;
use Dom\Node;

/**
 * Parses a piece of a page and writes it back out.
 *
 * A post is a fragment, not a document, and the two used to be confused: the parser wrapped the
 * text in html/body and every caller cut that wrapper back off by hand, each in its own way.
 * Here the wrapper is where a fragment is supposed to be parsed — the body is the context the
 * HTML5 rules are written for — and only its contents are ever written out.
 *
 * The parser behind it is the HTML5 one PHP 8.4 ships in ext-dom, which is why the CMS needs no
 * DOM library of its own any more.
 */
final readonly class HtmlFragment
{
    public function parse(string $html): HTMLDocument
    {
        return HTMLDocument::createFromString($html, LIBXML_NOERROR);
    }

    public function serialize(HTMLDocument $document): string
    {
        return $document->body->innerHTML;
    }

    /**
     * The nodes a piece of HTML parses into, ready to be put into $document.
     *
     * Building markup as a string and handing it here is how a transformer produces anything
     * more than a single element. It is also the only way markup crosses into the document, so
     * a transformer never concatenates HTML into an existing node.
     *
     * @return list<Node>
     */
    public function nodes(HTMLDocument $document, string $html): array
    {
        $holder = $document->createElement('div');
        $holder->innerHTML = $html;

        return iterator_to_array($holder->childNodes, false);
    }

    /**
     * The top-level nodes of the fragment — the blocks the author wrote, in order.
     *
     * @return list<Node>
     */
    public function topLevelNodes(HTMLDocument $document): array
    {
        return iterator_to_array($document->body->childNodes, false);
    }
}
