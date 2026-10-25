<?php

declare(strict_types=1);

namespace Johncms\Modules\News\Application\UseCases;

use Johncms\Modules\News\Domain\Repository\NewsSectionRepositoryInterface;
use Johncms\Modules\News\Domain\Services\SectionTree;

/**
 * Every section of the news with its name, as a tree: for the paths beside the articles and the
 * choices of a section.
 */
final readonly class GetSectionTreeUseCase
{
    public function __construct(
        private NewsSectionRepositoryInterface $sections,
    ) {
    }

    public function execute(): SectionTree
    {
        return new SectionTree($this->sections->getParents(), $this->sections->getNames());
    }
}
