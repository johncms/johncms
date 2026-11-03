<?php

declare(strict_types=1);

namespace Johncms\Modules\News\Application\DTO;

final readonly class NewsLegacyImportReportDTO
{
    /**
     * @param array<int, int> $sections Old ID => collection section ID.
     * @param array<int, int> $articles Old ID => collection item ID.
     * @param array<int, string> $urls Old article ID => verified canonical URL.
     * @param list<string> $warnings Explicit changes and missing legacy files.
     */
    public function __construct(
        public array $sections,
        public array $articles,
        public array $urls,
        public array $warnings,
        public int $excludedSections,
        public int $excludedArticles,
    ) {
    }

    /** @return list<string> */
    public function lines(): array
    {
        return [sprintf('News: imported %d sections and %d articles; excluded %d sections and %d articles.', count($this->sections), count($this->articles), $this->excludedSections, $this->excludedArticles), ...$this->warnings];
    }
}
