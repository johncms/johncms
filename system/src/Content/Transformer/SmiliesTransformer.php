<?php

declare(strict_types=1);

namespace Johncms\Content\Transformer;

use Dom\Element;
use Dom\HTMLDocument;
use Dom\Node;
use Dom\Text;
use Johncms\Content\ContentContext;
use Johncms\Content\Html\HtmlFragment;
use Johncms\Smilies\SmiliesRendererInterface;

/**
 * Renders the smiley codes of a text.
 *
 * It runs over the text nodes of the parsed post rather than over its HTML, which is what keeps
 * a code out of the places it never meant to be: the value of an attribute — a title or an
 * address that happens to contain `:)` — and a block of code, where the author is showing the
 * characters themselves.
 */
final readonly class SmiliesTransformer implements ContentTransformerInterface
{
    /**
     * Elements whose text is not prose: what stands inside them is shown as it was typed.
     */
    private const SKIPPED = ['code', 'pre', 'script', 'style', 'textarea'];

    public function __construct(
        private SmiliesRendererInterface $smilies,
        private HtmlFragment $fragment,
    ) {
    }

    /**
     * Last: a smiley is drawn over the finished text, and the markup the other steps build is
     * not prose the reader wrote.
     */
    public function priority(): int
    {
        return -100;
    }

    public function transform(HTMLDocument $document, ContentContext $context): void
    {
        $map = $this->escapedMap($this->smilies->map($context->adminSmilies));
        if ($map === []) {
            return;
        }

        // Collected first: replacing a node while walking the live list of its parent would skip
        // the node that moves into the freed place.
        foreach ($this->textNodes($document) as $text) {
            $this->replaceIn($document, $text, $map);
        }
    }

    /**
     * @param array<string, string> $map
     */
    private function replaceIn(HTMLDocument $document, Text $text, array $map): void
    {
        // The haystack is escaped and so are the codes, so the two match the way they did when
        // the replacement ran over the HTML of the whole post. What comes out is a fragment: the
        // text of the reader, escaped, with the markup of the smilies between the pieces.
        $escaped = htmlspecialchars($text->textContent, ENT_QUOTES, 'UTF-8');
        $replaced = strtr($escaped, $map);
        if ($replaced === $escaped) {
            return;
        }

        $text->replaceWith(...$this->fragment->nodes($document, $replaced));
    }

    /**
     * @param array<string, string> $map
     * @return array<string, string>
     */
    private function escapedMap(array $map): array
    {
        $escaped = [];
        foreach ($map as $code => $html) {
            $escaped[htmlspecialchars((string) $code, ENT_QUOTES, 'UTF-8')] = $html;
        }

        return $escaped;
    }

    /**
     * @return list<Text>
     */
    private function textNodes(Node $node): array
    {
        $found = [];
        foreach (iterator_to_array($node->childNodes, false) as $child) {
            if ($child instanceof Text) {
                $found[] = $child;
                continue;
            }

            if ($child instanceof Element && in_array($child->localName, self::SKIPPED, true)) {
                continue;
            }

            $found = array_merge($found, $this->textNodes($child));
        }

        return $found;
    }
}
