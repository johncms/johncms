<?php

declare(strict_types=1);

namespace Johncms\Modules\News\Application\DTO;

/**
 * The settings of the news as the form of the panel sends them. The templates of the meta tags of
 * a section and of an article take #section_name# and #article_name#.
 */
final readonly class NewsSettingsDTO
{
    public function __construct(
        public bool $homepageShow,
        public int $homepageQuantity,
        public int $homepageDays,
        public string $title = '',
        public string $metaKeywords = '',
        public string $metaDescription = '',
        public string $sectionTitle = '',
        public string $sectionMetaKeywords = '',
        public string $sectionMetaDescription = '',
        public string $articleTitle = '',
        public string $articleMetaKeywords = '',
        public string $articleMetaDescription = '',
    ) {
    }
}
