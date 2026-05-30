<?php

declare(strict_types=1);

namespace Johncms\Modules\Mail\Application\Services;

use Johncms\System\Legacy\Tools;

/**
 * Converts legacy BBCode/plain-text mail messages into the HTML format
 * used by the CKEditor-based mail composer.
 */
final readonly class MailBbcodeToHtmlConverter
{
    public function __construct(
        private Tools $tools,
    ) {
    }

    public function convert(string $text): string
    {
        if (trim($text) === '') {
            return '';
        }

        // The message has already been converted to HTML, leave it untouched.
        if ($this->isHtml($text)) {
            return $text;
        }

        // Reuse the legacy rendering pipeline (escaping, line breaks, BBCode tags,
        // links) to produce HTML identical to the previous on-screen output.
        return $this->tools->checkout($text, 1, 1);
    }

    private function isHtml(string $text): bool
    {
        return (bool) preg_match('~<(br|p|div|span|a|blockquote|pre|ul|ol|li|img)\b~i', $text);
    }
}
