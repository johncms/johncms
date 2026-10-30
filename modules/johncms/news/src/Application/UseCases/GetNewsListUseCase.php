<?php

declare(strict_types=1);

namespace Johncms\Modules\News\Application\UseCases;

use DateTimeImmutable;
use DateTimeInterface;
use Illuminate\Database\Eloquent\Collection;
use Johncms\Modules\News\Domain\Enums\NewsListTab;
use Johncms\Modules\News\Domain\Models\NewsArticle;
use Johncms\Modules\News\Domain\Models\NewsSection;
use Johncms\Modules\News\Domain\Repository\NewsArticleRepositoryInterface;
use Johncms\Modules\News\Domain\Repository\NewsSectionRepositoryInterface;
use Johncms\Modules\News\Domain\Services\SectionTree;
use Johncms\Modules\News\Domain\ValueObjects\NewsListFilter;
use Johncms\Users\User;

/**
 * The list of the news in the panel: the articles or the sections the filter lets through, one
 * page at a time, and what the controls around it need — the tree for the paths, the authors.
 *
 * The section of the filter is turned into the ids of its subtree here, from the tree the caller
 * got from GetSectionTreeUseCase: the repositories only ever see a list of ids.
 */
final readonly class GetNewsListUseCase
{
    public function __construct(
        private NewsArticleRepositoryInterface $articles,
        private NewsSectionRepositoryInterface $sections,
    ) {
    }

    /**
     * What each tab holds, whatever the list is narrowed to.
     *
     * @return array<string, int> Keyed by the value of NewsListTab.
     */
    public function tabCounts(): array
    {
        return [
            NewsListTab::ARTICLES->value => $this->articles->countAll(),
            NewsListTab::SECTIONS->value => $this->sections->countAll(),
        ];
    }

    public function countArticles(NewsListFilter $filter, SectionTree $tree, ?DateTimeInterface $now = null): int
    {
        return $this->articles->countList($filter, $this->articleSections($filter, $tree), $now ?? new DateTimeImmutable());
    }

    /**
     * @return Collection<int, NewsArticle>
     */
    public function getArticles(NewsListFilter $filter, SectionTree $tree, int $limit, int $offset, ?DateTimeInterface $now = null): Collection
    {
        return $this->articles->getList($filter, $this->articleSections($filter, $tree), $now ?? new DateTimeImmutable(), $limit, $offset);
    }

    /** @return Collection<int, NewsSection> */
    public function getAllSectionsWithArticleCounts(): Collection
    {
        return $this->sections->getAllWithArticleCounts();
    }

    /**
     * @return Collection<int, User>
     */
    public function authors(): Collection
    {
        return $this->articles->getAuthors();
    }

    /**
     * The articles of a section stand in it or under it; those of the root, outside every section.
     *
     * @return list<int>|null
     */
    private function articleSections(NewsListFilter $filter, SectionTree $tree): ?array
    {
        return match ($filter->section) {
            null                 => null,
            NewsListFilter::ROOT => [NewsListFilter::ROOT],
            default              => $tree->subtreeOf($filter->section),
        };
    }
}
