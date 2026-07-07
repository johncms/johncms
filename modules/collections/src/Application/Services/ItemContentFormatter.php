<?php

declare(strict_types=1);

namespace Johncms\Modules\Collections\Application\Services;

use HTMLPurifier;

/**
 * Sanitizes rich HTML item content on output (escape/sanitize-on-output). Item
 * text is stored raw and purified only when rendered on the public site.
 */
final readonly class ItemContentFormatter
{
    public function __construct(
        private HTMLPurifier $purifier,
    ) {
    }

    public function format(?string $html): ?string
    {
        if ($html === null || $html === '') {
            return null;
        }

        return $this->purifier->purify($html);
    }
}
