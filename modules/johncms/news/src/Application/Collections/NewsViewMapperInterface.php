<?php

declare(strict_types=1);

namespace Johncms\Modules\News\Application\Collections;

use Johncms\Modules\Collections\Application\DTO\CollectionRecordDTO;
use Johncms\Modules\Collections\Application\DTO\CollectionItemDTO;
use Johncms\Modules\Collections\Application\DTO\CollectionSectionDTO;
use Johncms\Modules\Collections\Application\DTO\CollectionSectionStatsDTO;
use Johncms\Modules\News\Application\DTO\NewsArticleViewDTO;
use Johncms\Modules\News\Application\DTO\NewsSectionViewDTO;

interface NewsViewMapperInterface
{
    /** @param list<CollectionItemDTO> $items
     * @return list<NewsArticleViewDTO>
     */
    public function articles(CollectionRecordDTO $collection, array $items): array;

    public function section(CollectionRecordDTO $collection, CollectionSectionDTO $section): NewsSectionViewDTO;

    /**
     * @param list<CollectionSectionDTO> $sections
     * @param array<int, CollectionSectionStatsDTO> $stats What each section holds, keyed by section id.
     * @return list<NewsSectionViewDTO>
     */
    public function sections(CollectionRecordDTO $collection, array $sections, array $stats = []): array;

    /** @return array{title: string, page_title: string, description: string, keywords: string} */
    public function rootMetadata(): array;

    /** The short description of the main page of the news, plain text; empty when none is set. */
    public function rootSummary(): string;
}
