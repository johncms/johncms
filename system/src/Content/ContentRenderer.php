<?php

declare(strict_types=1);

namespace Johncms\Content;

use Johncms\Content\Html\HtmlFragment;
use Johncms\Content\Transformer\ContentTransformerRegistry;
use Johncms\Security\HtmlSanitizerInterface;
use Twig\Markup;

/**
 * The content pipeline: clean the HTML, then let every registered step work on it.
 *
 * The text is parsed once and the steps share that tree. What used to happen instead was a
 * parse and a serialize per step, on top of the parse the sanitizer does — a post with two
 * steps in front of it went through four of them.
 */
final readonly class ContentRenderer implements ContentRendererInterface
{
    public function __construct(
        private HtmlSanitizerInterface $sanitizer,
        private ContentTransformerRegistry $transformers,
        private HtmlFragment $fragment,
    ) {
    }

    public function render(string $html, ContentContext $context = new ContentContext()): Markup
    {
        return new Markup($this->html($html, $context), 'UTF-8');
    }

    public function renderOrNull(string $html, ContentContext $context = new ContentContext()): ?Markup
    {
        $rendered = $this->html($html, $context);

        return $rendered === '' ? null : new Markup($rendered, 'UTF-8');
    }

    public function toPlainText(string $html, ContentContext $context = new ContentContext()): string
    {
        $text = html_entity_decode(strip_tags($this->html($html, $context)), ENT_QUOTES | ENT_HTML5, 'UTF-8');

        return trim((string) preg_replace('/\s+/u', ' ', $text));
    }

    private function html(string $html, ContentContext $context): string
    {
        // Nothing to clean and nothing to walk. The check also keeps the whole pipeline off the
        // hot path of a page whose rows mostly carry no text.
        if (trim($html) === '') {
            return '';
        }

        $sanitized = $this->sanitizer->sanitize($html, $context->policy);
        if (trim($sanitized) === '') {
            return '';
        }

        $document = $this->fragment->parse($sanitized);
        foreach ($this->transformers->all() as $transformer) {
            $transformer->transform($document, $context);
        }

        return $this->fragment->serialize($document);
    }
}
