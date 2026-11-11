<?php

declare(strict_types=1);

namespace Johncms\Modules\News\Application\Collections;

use Johncms\Auth\CurrentUser;
use Johncms\Content\ContentRendererInterface;
use Johncms\Modules\Collections\Application\Api\CollectionSchemaApiInterface;
use Johncms\Modules\Collections\Application\DTO\CollectionRecordDTO;
use Johncms\Modules\Collections\Application\DTO\CollectionItemDTO;
use Johncms\Modules\Collections\Application\DTO\CollectionSectionDTO;
use Johncms\Modules\Collections\Application\DTO\CollectionSectionStatsDTO;
use Johncms\Modules\News\Application\DTO\NewsArticleViewDTO;
use Johncms\Modules\News\Application\DTO\NewsAuthorDTO;
use Johncms\Modules\News\Application\DTO\NewsSectionViewDTO;
use Johncms\Modules\News\Domain\Repository\NewsConfigRepositoryInterface;
use Johncms\Modules\News\Domain\Repository\NewsDiscussionRepositoryInterface;
use Johncms\Modules\News\Domain\ValueObjects\NewsDiscussionStats;
use Johncms\Users\Repository\UserRepositoryInterface;
use Johncms\Utils\DateFormatterInterface;

final readonly class NewsViewMapper implements NewsViewMapperInterface
{
    public function __construct(
        private ContentRendererInterface $content,
        private CollectionSchemaApiInterface $schema,
        private NewsCollectionOwnerUrls $urls,
        private NewsConfigRepositoryInterface $settings,
        private NewsDiscussionRepositoryInterface $discussion,
        private UserRepositoryInterface $users,
        private CurrentUser $user,
        private DateFormatterInterface $dates,
    ) {
    }

    public function articles(CollectionRecordDTO $collection, array $items): array
    {
        if ($items === []) {
            return [];
        }
        $roles = [];
        foreach ($this->schema->getFields($collection->id) as $field) {
            if ($field->role !== null) {
                $roles[$field->role] = $field->code;
            }
        }
        $ids = array_map(static fn (CollectionItemDTO $item): int => $item->id, $items);
        $urls = $this->urls->itemUrls($collection, $items);
        $authors = $this->users->findMany(array_values(array_unique(array_filter(array_map(static fn (CollectionItemDTO $item): ?int => $item->createdBy, $items)))));
        $stats = $this->discussion->stats($ids, $this->user->id());
        $settings = $this->settings->getSettings();
        $result = [];
        foreach ($items as $item) {
            $field = static fn (string $role): mixed => isset($roles[$role]) ? ($item->values[$roles[$role]] ?? null) : null;
            $text = static fn (string $role): string => is_string($field($role)) ? $field($role) : '';
            $template = fn (string $key): string => $this->setting($settings, $key, '#article_name#', $item->name);
            $author = $item->createdBy !== null ? ($authors[$item->createdBy] ?? null) : null;
            $tags = $field('tags');
            $result[] = new NewsArticleViewDTO(
                $item,
                $urls[$item->id] ?? '',
                $this->content->renderOrNull($item->previewText ?? ''),
                $this->content->renderOrNull($item->detailText ?? ''),
                is_array($tags) ? array_values(array_filter($tags, 'is_string')) : [],
                $item->publishedAt !== null ? $this->dates->format($item->publishedAt->getTimestamp()) : '',
                $author !== null ? new NewsAuthorDTO(
                    id:         $author->id,
                    name:       $author->name,
                    rightsName: $author->rights_name,
                    joinedAt:   $author->datereg,
                ) : null,
                $stats[$item->id] ?? new NewsDiscussionStats(),
                $this->content->toPlainText($text('meta_title') ?: ($template('article_title') ?: $item->name)),
                $this->content->toPlainText($text('meta_description') ?: $template('article_meta_description'))
                    ?: ($this->content->toPlainText($item->previewText ?? '') ?: $this->content->toPlainText($item->detailText ?? '')),
                $this->content->toPlainText($text('meta_keywords') ?: $template('article_meta_keywords')),
                $item->publishedAt !== null ? $this->dates->day($item->publishedAt->getTimestamp()) : '',
            );
        }
        return $result;
    }

    public function section(CollectionRecordDTO $collection, CollectionSectionDTO $section): NewsSectionViewDTO
    {
        return $this->sections($collection, [$section])[0];
    }

    public function sections(CollectionRecordDTO $collection, array $sections, array $stats = []): array
    {
        $settings = $this->settings->getSettings();
        $urls = $this->urls->sectionUrls($collection, $sections);
        return array_map(fn (CollectionSectionDTO $section): NewsSectionViewDTO => $this->sectionView($section, $settings, $urls[$section->id] ?? '', $stats[$section->id] ?? null), $sections);
    }

    /** @param array<string, mixed> $settings */
    private function sectionView(CollectionSectionDTO $section, array $settings, string $url, ?CollectionSectionStatsDTO $stats): NewsSectionViewDTO
    {
        $summary = trim($section->summary ?? '');
        $text = static fn (string $key): string => is_string($section->metadata[$key] ?? null) ? $section->metadata[$key] : '';
        $template = fn (string $key): string => $this->setting($settings, $key, '#section_name#', $section->name);
        return new NewsSectionViewDTO(
            $section,
            $url,
            $this->content->renderOrNull($section->description ?? ''),
            $this->content->toPlainText($text('meta_title') ?: ($template('section_title') ?: $section->name)),
            $this->content->toPlainText($text('meta_description') ?: $template('section_meta_description'))
                ?: ($summary ?: $this->content->toPlainText($section->description ?? '')),
            $this->content->toPlainText($text('meta_keywords') ?: $template('section_meta_keywords')),
            $summary,
            $stats->items ?? 0,
            $stats?->lastPublishedAt !== null ? $this->dates->day($stats->lastPublishedAt->getTimestamp()) : '',
        );
    }

    public function rootMetadata(): array
    {
        $settings = $this->settings->getSettings();
        $title = $this->content->toPlainText($this->setting($settings, 'title')) ?: d__('news', 'News');
        return [
            'title' => $title, 'page_title' => $title,
            'description' => $this->content->toPlainText($this->setting($settings, 'meta_description')) ?: ($this->rootSummary() ?: $title),
            'keywords' => $this->content->toPlainText($this->setting($settings, 'meta_keywords')),
        ];
    }

    public function rootSummary(): string
    {
        return trim($this->setting($this->settings->getSettings(), 'summary'));
    }

    /** @param array<string, mixed> $settings */
    private function setting(array $settings, string $key, string $placeholder = '', string $name = ''): string
    {
        $value = is_scalar($settings[$key] ?? null) ? (string) $settings[$key] : '';
        return $placeholder !== '' ? str_replace($placeholder, $name, $value) : $value;
    }
}
