<?php

declare(strict_types=1);

namespace Johncms\Modules\News\Domain\Repository;

use DateTimeInterface;
use Illuminate\Database\Eloquent\Collection;
use Johncms\Modules\News\Domain\Models\NewsArticle;
use Johncms\Modules\News\Domain\ValueObjects\NewsListFilter;
use Johncms\Users\User;

interface NewsArticleRepositoryInterface
{
    public function findById(int $id): ?NewsArticle;

    public function countAll(): int;

    /**
     * The articles the list lets through. The sections are worked out by the caller from the tree.
     *
     * @param list<int>|null $sectionIds The sections the articles stand in, 0 for the root (the
     *                                   articles outside every section); null for any.
     */
    public function countList(NewsListFilter $filter, ?array $sectionIds, DateTimeInterface $now): int;

    /**
     * One page of them, each with its author and the number of its comments.
     *
     * @param list<int>|null $sectionIds As for countList().
     * @return Collection<int, NewsArticle>
     */
    public function getList(NewsListFilter $filter, ?array $sectionIds, DateTimeInterface $now, int $limit, int $offset): Collection;

    /**
     * The accounts that wrote an article, by name.
     *
     * @return Collection<int, User>
     */
    public function getAuthors(): Collection;

    /**
     * @param list<int> $sectionIds
     */
    public function countInSections(array $sectionIds): int;

    /**
     * Whether another article of the section holds the code. 0 is the root, where the articles
     * stored without a section stand as well.
     */
    public function isCodeTaken(string $code, int $sectionId, ?int $exceptId = null): bool;

    /**
     * @param array<string, mixed> $attributes
     */
    public function create(array $attributes): NewsArticle;

    /**
     * @param array<string, mixed> $attributes
     */
    public function update(NewsArticle $article, array $attributes): void;

    /**
     * The text the search of the news looks the article up by.
     */
    public function index(int $articleId, string $text): void;

    public function delete(NewsArticle $article): void;

    /**
     * @param list<int> $sectionIds
     */
    public function deleteInSections(array $sectionIds): void;
}
