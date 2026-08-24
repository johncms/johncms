<?php

declare(strict_types=1);

namespace Johncms\Modules\Collections\Application\Services;

use Johncms\Security\HtmlSanitizerInterface;
use Twig\Markup;

/**
 * Sanitizes rich HTML item content on output (escape/sanitize-on-output). Item
 * text is stored raw and purified only when rendered on the public site.
 */
final readonly class ItemContentFormatter
{
    public function __construct(
        private HtmlSanitizerInterface $sanitizer,
    ) {
    }

    /**
     * Sanitized content, or null when there is none. It is markup by contract — a template
     * prints it as it is — and null rather than empty markup, because markup is an object and
     * an object is truthy however empty it is.
     */
    public function format(?string $html): ?Markup
    {
        if ($html === null || $html === '') {
            return null;
        }

        return new Markup($this->sanitizer->sanitize($html), 'UTF-8');
    }
}
