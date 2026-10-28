<?php

declare(strict_types=1);

namespace Johncms\Modules\Forum\Application\DTO;

/**
 * A section of the forum as the form of the panel sends it, the text as it was typed.
 *
 * The order is null when the field was left empty: the section then goes to the end of its parent.
 */
final readonly class SectionFormDTO
{
    public function __construct(
        public int $parentId,
        public string $name,
        public string $description = '',
        public ?int $sort = null,
        public int $sectionType = 0,
        public int $access = 0,
        public string $metaKeywords = '',
        public string $metaDescription = '',
    ) {
    }
}
