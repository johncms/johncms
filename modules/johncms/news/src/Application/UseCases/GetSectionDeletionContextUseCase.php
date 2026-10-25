<?php

declare(strict_types=1);

namespace Johncms\Modules\News\Application\UseCases;

use Johncms\Modules\News\Application\DTO\SectionDeletionContextDTO;
use Johncms\Modules\News\Application\Exceptions\SectionNotFoundException;
use Johncms\Modules\News\Domain\Repository\NewsArticleRepositoryInterface;
use Johncms\Modules\News\Domain\Repository\NewsSectionRepositoryInterface;
use Johncms\Modules\News\Domain\Services\SectionTree;

/**
 * What the confirmation of deleting a section says: the sections under it and the articles of
 * all of them, which go with it.
 */
final readonly class GetSectionDeletionContextUseCase
{
    public function __construct(
        private NewsSectionRepositoryInterface $sections,
        private NewsArticleRepositoryInterface $articles,
    ) {
    }

    /**
     * @throws SectionNotFoundException
     */
    public function execute(int $id): SectionDeletionContextDTO
    {
        $section = $this->sections->findById($id) ?? throw new SectionNotFoundException();
        $subtree = (new SectionTree($this->sections->getParents()))->subtreeOf($section->id);

        return new SectionDeletionContextDTO($section, count($subtree) - 1, $this->articles->countInSections($subtree));
    }
}
