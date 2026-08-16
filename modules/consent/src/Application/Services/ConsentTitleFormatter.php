<?php

declare(strict_types=1);

namespace Johncms\Modules\Consent\Application\Services;

use Johncms\Security\HtmlPolicy;
use Johncms\Security\HtmlSanitizerInterface;

/**
 * Prepares a consent title for output.
 *
 * The title is shown next to the checkbox in a form and may contain inline HTML,
 * typically links to other pages. It is stored raw and sanitized on output with an
 * inline-only policy. Places where markup makes no sense (page titles, breadcrumbs,
 * admin tables) use the plain text variant instead.
 */
final readonly class ConsentTitleFormatter
{
    public function __construct(
        private HtmlSanitizerInterface $sanitizer,
    ) {
    }

    public function toHtml(string $title): string
    {
        return $this->sanitizer->sanitize($title, HtmlPolicy::Inline);
    }

    public function toPlainText(string $title): string
    {
        return $this->sanitizer->toPlainText($title, HtmlPolicy::Inline);
    }
}
