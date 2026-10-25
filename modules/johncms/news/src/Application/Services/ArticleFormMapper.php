<?php

declare(strict_types=1);

namespace Johncms\Modules\News\Application\Services;

use DateTimeImmutable;
use DateTimeInterface;
use Johncms\Modules\News\Application\DTO\ArticleFormDTO;
use Johncms\Modules\News\Application\UseCases\SaveArticleUseCase;
use Johncms\Modules\News\Domain\Enums\ArticleStatus;
use Johncms\Modules\News\Domain\Models\NewsArticle;
use Johncms\Modules\News\Domain\Services\SectionTree;
use Johncms\Modules\News\Domain\ValueObjects\NewsListFilter;

/**
 * The form of an article as the article holds it, and what news::admin/article-form draws of it — the values, the choices of a section, the status the dates
 * give and the facts about a saved article.
 */
final readonly class ArticleFormMapper
{
    public function fromArticle(NewsArticle $article): ArticleFormDTO
    {
        return new ArticleFormDTO(
            active: (bool) $article->active,
            sectionId: (int) $article->section_id,
            name: $article->name,
            code: $article->code,
            pageTitle: (string) $article->page_title,
            activeFrom: $this->formatted($article->getRawOriginal('active_from')),
            activeTo: $this->formatted($article->getRawOriginal('active_to')),
            keywords: (string) $article->keywords,
            description: (string) $article->description,
            tags: implode(', ', $article->tags),
            previewText: (string) $article->getRawOriginal('preview_text'),
            text: (string) $article->getRawOriginal('text'),
        );
    }

    /**
     * A new article: switched on, in the section the list was narrowed to.
     */
    public function blank(int $sectionId): ArticleFormDTO
    {
        return new ArticleFormDTO(active: true, sectionId: $sectionId, name: '', code: '');
    }

    /**
     * @return array<string, mixed>
     */
    public function fields(ArticleFormDTO $form): array
    {
        return [
            'active'       => $form->active,
            'section_id'   => $form->sectionId,
            'name'         => $form->name,
            'code'         => $form->code,
            'page_title'   => $form->pageTitle,
            'active_from'  => $form->activeFrom,
            'active_to'    => $form->activeTo,
            'keywords'     => $form->keywords,
            'description'  => $form->description,
            'tags'         => $form->tags,
            'preview_text' => $form->previewText,
            'text'         => $form->text,
        ];
    }

    /**
     * The root and every section under its parent, each by its path.
     *
     * @return list<array{id: int, title: string}>
     */
    public function sectionOptions(SectionTree $tree): array
    {
        $options = [['id' => NewsListFilter::ROOT, 'title' => __('Root')]];

        foreach ($tree->ordered() as $id) {
            $options[] = ['id' => $id, 'title' => implode(' › ', $tree->pathOf($id))];
        }

        return $options;
    }

    /**
     * Where the article stands with the readers by the values of the form: what saving it gives.
     * A date the form cannot read counts as none.
     *
     * @return array{status: string, title: string, date: int}
     */
    public function status(ArticleFormDTO $form, ?DateTimeInterface $now = null): array
    {
        $from = $this->date($form->activeFrom);
        $to = $this->date($form->activeTo);
        $status = ArticleStatus::of($form->active, $from, $to, $now ?? new DateTimeImmutable());

        return [
            'status' => $status->value,
            'title'  => NewsListControlsMapper::statusTitle($status),
            'date'   => match ($status) {
                ArticleStatus::SCHEDULED => $from?->getTimestamp() ?? 0,
                ArticleStatus::EXPIRED   => $to?->getTimestamp() ?? 0,
                default                  => 0,
            },
        ];
    }

    /**
     * What a saved article is beyond its form.
     *
     * @return array<string, mixed>
     */
    public function facts(NewsArticle $article): array
    {
        return [
            'id'         => $article->id,
            'author'     => $article->author->name ?? '',
            'author_url' => $article->author !== null ? '/admin/users/' . $article->author->id : '',
            'created'    => $this->timestamp($article->getRawOriginal('created_at')),
            'updated'    => $this->timestamp($article->getRawOriginal('updated_at')),
            'views'      => $article->view_count,
        ];
    }

    private function formatted(mixed $raw): string
    {
        $timestamp = $this->timestamp($raw);

        return $timestamp > 0 ? date(SaveArticleUseCase::DATE_FORMAT, $timestamp) : '';
    }

    private function timestamp(mixed $raw): int
    {
        $timestamp = is_string($raw) && $raw !== '' ? strtotime($raw) : false;

        return $timestamp !== false ? $timestamp : 0;
    }

    private function date(string $value): ?DateTimeImmutable
    {
        $date = DateTimeImmutable::createFromFormat('!' . SaveArticleUseCase::DATE_FORMAT, trim($value));

        return $date !== false ? $date : null;
    }
}
