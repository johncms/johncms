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

    /**
     * The text for the first of the languages that has one: the language of the visitor, then the
     * default language of the site, then English — the order the contacts page follows too. Empty
     * when none has a text, and the banner then says the translated default.
     *
     * @param array<array-key, mixed> $texts     The text of each language, keyed by its code.
     * @param list<string>            $languages In the order they are tried.
     */
    public function pick(array $texts, array $languages): string
    {
        foreach ($languages as $language) {
            $text = $texts[$language] ?? '';
            if (is_string($text) && trim($text) !== '') {
                return $text;
            }
        }

        return '';
    }
}
