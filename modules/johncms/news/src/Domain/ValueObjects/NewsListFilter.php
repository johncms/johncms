<?php

declare(strict_types=1);

namespace Johncms\Modules\News\Domain\ValueObjects;

use Johncms\Modules\Admin\Domain\Enums\SortDirection;
use Johncms\Modules\News\Domain\Enums\ArticleStatus;
use Johncms\Modules\News\Domain\Enums\NewsListSort;
use Johncms\Modules\News\Domain\Enums\NewsListTab;

/**
 * What the list of the news in the panel shows, what it is narrowed to and how it is ordered.
 *
 * The section narrows both tabs to its subtree: the articles of the section and of the sections
 * under it, or those sections themselves. 0 is the root: the articles outside every section, the
 * sections at the top. The status and the author are of the articles alone.
 */
final readonly class NewsListFilter
{
    public const ROOT = 0;

    public NewsListSort $sort;
    public SortDirection $direction;

    public function __construct(
        public NewsListTab $tab = NewsListTab::ARTICLES,
        public string $search = '',
        public ?int $section = null,
        public ?ArticleStatus $status = null,
        public ?int $author = null,
        ?NewsListSort $sort = null,
        ?SortDirection $direction = null,
    ) {
        // A column the tab does not have orders nothing: the tab takes its own order instead.
        $this->sort = $sort !== null && $sort->isOf($tab) ? $sort : NewsListSort::defaultFor($tab);
        $this->direction = $direction ?? $this->sort->defaultDirection();
    }

    public function withTab(NewsListTab $tab): self
    {
        // The order belongs to the tab it was chosen on.
        return new self($tab, $this->search, $this->section, $this->status, $this->author);
    }

    public function withSearch(string $search): self
    {
        return new self($this->tab, $search, $this->section, $this->status, $this->author, $this->sort, $this->direction);
    }

    public function withSection(?int $section): self
    {
        return new self($this->tab, $this->search, $section, $this->status, $this->author, $this->sort, $this->direction);
    }

    public function withStatus(?ArticleStatus $status): self
    {
        return new self($this->tab, $this->search, $this->section, $status, $this->author, $this->sort, $this->direction);
    }

    public function withAuthor(?int $author): self
    {
        return new self($this->tab, $this->search, $this->section, $this->status, $author, $this->sort, $this->direction);
    }

    public function withSort(NewsListSort $sort, SortDirection $direction): self
    {
        return new self($this->tab, $this->search, $this->section, $this->status, $this->author, $sort, $direction);
    }

    public function isDefaultOrder(): bool
    {
        return $this->sort === NewsListSort::defaultFor($this->tab) && $this->direction === $this->sort->defaultDirection();
    }
}
