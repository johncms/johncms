<?php

declare(strict_types=1);

namespace Johncms\Modules\News\Application\DTO;

use Johncms\Modules\Collections\Application\DTO\CollectionItemDTO;
use Johncms\Modules\News\Domain\ValueObjects\NewsDiscussionStats;
use Twig\Markup;

final readonly class NewsArticleViewDTO
{
    /** @param list<string> $tags */
    public function __construct(
        public CollectionItemDTO $record,
        public string $url,
        public ?Markup $previewText,
        public ?Markup $detailText,
        public array $tags,
        public string $displayDate,
        public ?NewsAuthorDTO $author,
        public NewsDiscussionStats $stats,
        public string $metaTitle,
        public string $metaDescription,
        public string $metaKeywords,
        public string $shortDate = '',
    ) {
    }

    /**
     * The established news templates consume this view, without an ORM model.
     * @return array<string, mixed>
     */
    public function toTemplate(): array
    {
        return [
            'id' => $this->record->id, 'name' => $this->record->name, 'code' => $this->record->code,
            'section_id' => $this->record->sectionId, 'url' => $this->url, 'preview_text_safe' => $this->previewText,
            'text_safe' => $this->detailText, 'view_count' => $this->record->viewCount, 'tags' => $this->tags,
            'display_date' => $this->displayDate, 'author' => $this->author, 'rating' => $this->stats->rating,
            'short_date' => $this->shortDate,
            'current_vote' => $this->stats->currentVote, 'comments_count' => $this->stats->comments,
        ];
    }

    /** @return array{title: string, page_title: string, description: string, keywords: string} */
    public function metadata(): array
    {
        return ['title' => $this->metaTitle, 'page_title' => $this->record->name, 'description' => $this->metaDescription, 'keywords' => $this->metaKeywords];
    }
}
