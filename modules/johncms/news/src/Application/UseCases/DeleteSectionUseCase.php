<?php

declare(strict_types=1);

namespace Johncms\Modules\News\Application\UseCases;

use Johncms\Modules\News\Application\Exceptions\SectionNotFoundException;
use Johncms\Modules\News\Application\Section;
use Johncms\Modules\News\Domain\Models\NewsSection;
use Johncms\Modules\News\Domain\Repository\NewsArticleRepositoryInterface;
use Johncms\Modules\News\Domain\Repository\NewsSectionRepositoryInterface;
use Johncms\Modules\News\Domain\Services\SectionTree;

/**
 * Deletes a section with everything under it: the subsections and the articles of all of them.
 * The rows stay, marked as deleted.
 */
final readonly class DeleteSectionUseCase
{
    public function __construct(
        private NewsSectionRepositoryInterface $sections,
        private NewsArticleRepositoryInterface $articles,
        private Section $sectionCache,
    ) {
    }

    /**
     * @return NewsSection The section as it was, for the caller to say where it stood.
     * @throws SectionNotFoundException
     */
    public function execute(int $id): NewsSection
    {
        $section = $this->sections->findById($id) ?? throw new SectionNotFoundException();
        $subtree = (new SectionTree($this->sections->getParents()))->subtreeOf($section->id);

        $this->articles->deleteInSections($subtree);
        $this->sections->deleteByIds($subtree);
        $this->sectionCache->clearCache();

        return $section;
    }
}
