<?php

declare(strict_types=1);

namespace Johncms\Modules\News\Application\Collections;

use Johncms\Modules\Collections\Application\DTO\CollectionRecordDTO;
use Johncms\Modules\News\Application\DTO\NewsQueryDTO;
use Johncms\Modules\News\Application\DTO\NewsPageDTO;
use Johncms\Modules\News\Application\DTO\NewsArticleViewDTO;
use Johncms\Modules\News\Application\DTO\NewsSectionViewDTO;
use Johncms\Sitemap\SitemapUrlEntry;

interface NewsReadServiceInterface
{
    public function collection(): CollectionRecordDTO;

    public function page(NewsQueryDTO $query = new NewsQueryDTO()): NewsPageDTO;

    public function count(NewsQueryDTO $query = new NewsQueryDTO()): int;

    public function findById(int $itemId): ?NewsArticleViewDTO;

    public function findByCode(string $category, string $code): ?NewsArticleViewDTO;

    public function preview(int $itemId): ?NewsArticleViewDTO;

    /** @return list<NewsSectionViewDTO> */
    public function sections(?int $parentId = null): array;

    public function section(int $sectionId): ?NewsSectionViewDTO;

    /** Root resolves to null; a missing or hidden path throws NewsItemNotFoundException. */
    public function resolveSection(string $category): ?NewsSectionViewDTO;

    /** @return list<NewsSectionViewDTO> */
    public function breadcrumbs(int $sectionId): array;

    /** @return list<NewsArticleViewDTO> */
    public function homepage(): array;

    public function countNew(int $days): int;

    /** @return iterable<SitemapUrlEntry> */
    public function sitemap(string $homeUrl): iterable;

    public function registerView(int $itemId): void;

    /** @return array{title: string, page_title: string, description: string, keywords: string} */
    public function rootMetadata(): array;
}
