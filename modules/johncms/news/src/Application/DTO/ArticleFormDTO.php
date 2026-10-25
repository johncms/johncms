<?php

declare(strict_types=1);

namespace Johncms\Modules\News\Application\DTO;

/**
 * An article as the form of the panel sends it: the text as it was typed, the dates as
 * "dd.mm.yyyy hh:mm" or empty.
 */
final readonly class ArticleFormDTO
{
    /**
     * @param list<int> $attachedFiles The files the editor uploaded while the form was open.
     */
    public function __construct(
        public bool $active,
        public int $sectionId,
        public string $name,
        public string $code,
        public string $pageTitle = '',
        public string $activeFrom = '',
        public string $activeTo = '',
        public string $keywords = '',
        public string $description = '',
        public string $tags = '',
        public string $previewText = '',
        public string $text = '',
        public array $attachedFiles = [],
    ) {
    }
}
