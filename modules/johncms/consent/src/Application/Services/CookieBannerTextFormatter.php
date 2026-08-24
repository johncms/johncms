<?php

declare(strict_types=1);

namespace Johncms\Modules\Consent\Application\Services;

use Johncms\Security\HtmlPolicy;
use Johncms\Security\HtmlSanitizerInterface;

/**
 * Prepares the cookie banner text for output.
 *
 * The text is entered by an administrator and may contain inline HTML, typically links to
 * the cookie policy and the consent pages. It is stored raw and sanitized on output; the
 * banner stands on its own, so paragraphs are allowed in it.
 */
final readonly class CookieBannerTextFormatter
{
    public function __construct(
        private HtmlSanitizerInterface $sanitizer,
    ) {
    }

    public function toHtml(string $text): string
    {
        return $this->sanitizer->sanitize($text, HtmlPolicy::InlineWithParagraphs);
    }
}
