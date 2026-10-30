<?php

declare(strict_types=1);

namespace Johncms\Modules\News\Application\DTO;

use Johncms\Modules\News\Domain\Models\NewsSection;

/**
 * A section as the tree of the list shows it: how deep it stands below what the list is narrowed
 * to, whether the search found it or it only leads to what was found, and how many articles stand
 * in it and under it.
 */
final readonly class SectionTreeItemDTO
{
    public function __construct(
        public NewsSection $section,
        public int $depth,
        public bool $isMatch,
        public int $articles,
        public int $articlesInSubtree,
    ) {
    }
}
