<?php

declare(strict_types=1);

namespace Johncms\Modules\News\Application\DTO;

use Johncms\Modules\Collections\Application\DTO\CollectionSectionDTO;
use Twig\Markup;

final readonly class NewsSectionViewDTO
{
    public function __construct(
        public CollectionSectionDTO $record,
        public string $url,
        public ?Markup $text,
        public string $metaTitle,
        public string $metaDescription,
        public string $metaKeywords,
    ) {
    }

    /** @return array<string, mixed> */
    public function toTemplate(): array
    {
        return ['id' => $this->record->id, 'name' => $this->record->name, 'code' => $this->record->code, 'url' => $this->url, 'text_safe' => $this->text];
    }

    /** @return array{title: string, page_title: string, description: string, keywords: string} */
    public function metadata(): array
    {
        return ['title' => $this->metaTitle, 'page_title' => $this->record->name, 'description' => $this->metaDescription, 'keywords' => $this->metaKeywords];
    }
}
