<?php

declare(strict_types=1);

namespace Johncms\Modules\Collections\Application\DTO;

/**
 * A public listing row. The detail URL is built in the controller from the
 * item's own section path (sectionId) and code, so it is correct even when the
 * item is listed at the collection root while actually living in a section.
 */
final readonly class PublicItemDTO
{
    public function __construct(
        public string $code,
        public string $name,
        public ?string $previewText,
        public ?int $sectionId,
    ) {
    }
}
