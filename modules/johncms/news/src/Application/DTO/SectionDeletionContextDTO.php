<?php

declare(strict_types=1);

namespace Johncms\Modules\News\Application\DTO;

use Johncms\Modules\News\Domain\Models\NewsSection;

/**
 * What deleting a section takes with it.
 */
final readonly class SectionDeletionContextDTO
{
    public function __construct(
        public NewsSection $section,
        public int $subsections,
        public int $articles,
    ) {
    }
}
