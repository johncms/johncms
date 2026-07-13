<?php

declare(strict_types=1);

namespace Johncms\Modules\Consent\Application\DTO;

/**
 * A consent prepared for rendering as a checkbox on a form.
 */
final readonly class FormConsentDTO
{
    public function __construct(
        public int $id,
        /** Sanitized inline HTML, printed as-is. */
        public string $titleHtml,
        /** Link to the consent text page, null when the title must be shown without a link. */
        public ?string $url,
        public bool $isRequired,
        public string $version,
    ) {
    }
}
