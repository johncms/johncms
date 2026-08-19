<?php

declare(strict_types=1);

namespace Johncms\Content;

use Johncms\Security\HtmlPolicy;

/**
 * What the renderer needs to know about the text it is handed: how it may be cleaned, and what
 * the reader is allowed to see in it.
 *
 * Every transformer gets the same context, so a module that adds one can branch on the same
 * facts the built-in ones do without inventing a channel of its own.
 */
final readonly class ContentContext
{
    /**
     * @param HtmlPolicy|string $policy         A built-in policy, or the name a module declared.
     * @param bool              $adminSmilies   Whether the smilies reserved for the staff render.
     */
    public function __construct(
        public HtmlPolicy|string $policy = HtmlPolicy::RichContent,
        public bool $adminSmilies = false,
    ) {
    }
}
