<?php

declare(strict_types=1);

namespace Johncms\Modules\News\Application\DTO;

/**
 * A section as the form of the panel sends it, the text as it was typed.
 */
final readonly class SectionFormDTO
{
    public function __construct(
        public int $parentId,
        public string $name,
        public string $code,
        public string $keywords = '',
        public string $description = '',
        public string $text = '',
    ) {
    }
}
