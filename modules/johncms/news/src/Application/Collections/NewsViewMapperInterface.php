<?php

declare(strict_types=1);

namespace Johncms\Modules\News\Application\Collections;

use Johncms\Modules\Collections\Application\DTO\CollectionRecordDTO;
use Johncms\Modules\Collections\Application\DTO\CollectionItemDTO;
use Johncms\Modules\Collections\Application\DTO\CollectionSectionDTO;
use Johncms\Modules\News\Application\DTO\NewsArticleViewDTO;
use Johncms\Modules\News\Application\DTO\NewsSectionViewDTO;

interface NewsViewMapperInterface
{
    /** @param list<CollectionItemDTO> $items
     * @return list<NewsArticleViewDTO>
     */
    public function articles(CollectionRecordDTO $collection, array $items): array;

    public function section(CollectionRecordDTO $collection, CollectionSectionDTO $section): NewsSectionViewDTO;

    /** @return array{title: string, page_title: string, description: string, keywords: string} */
    public function rootMetadata(): array;
}
