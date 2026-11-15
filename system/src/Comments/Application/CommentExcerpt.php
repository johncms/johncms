<?php

declare(strict_types=1);

namespace Johncms\Comments\Application;

use Johncms\Content\Html\HtmlFragment;

/**
 * The start of a comment as plain words on one line: what an answer shows of the comment it
 * answers, and what a notification shows of the answer.
 *
 * A quote is left out: it is somebody else's words, and an answer that opens with a quote would
 * otherwise recall the comment it quoted rather than itself. The text is stored cleaned, so its
 * words are read straight from the tree; the template escapes them like any other text.
 */
final readonly class CommentExcerpt
{
    public const int LENGTH = 120;

    public function __construct(private HtmlFragment $fragment)
    {
    }

    public function of(string $html, int $length = self::LENGTH): string
    {
        $document = $this->fragment->parse($html);
        foreach ($document->body->querySelectorAll('blockquote') as $quote) {
            $quote->remove();
        }

        // The blocks are joined by a space, so that two paragraphs do not run into one word.
        foreach ($document->body->querySelectorAll('p, li, h2, h3, h4, br, figcaption, td') as $block) {
            $block->after(' ');
        }

        $plain = trim((string) preg_replace('/\s+/u', ' ', $document->body->textContent ?? ''));

        return mb_strimwidth($plain, 0, $length, '…', 'UTF-8');
    }
}
