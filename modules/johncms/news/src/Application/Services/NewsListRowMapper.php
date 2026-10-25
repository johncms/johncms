<?php

declare(strict_types=1);

namespace Johncms\Modules\News\Application\Services;

use DateTimeImmutable;
use DateTimeInterface;
use Illuminate\Database\Eloquent\Collection;
use Johncms\Modules\News\Domain\Enums\ArticleStatus;
use Johncms\Modules\News\Domain\Enums\NewsListTab;
use Johncms\Modules\News\Domain\Models\NewsArticle;
use Johncms\Modules\News\Domain\Models\NewsSection;
use Johncms\Modules\News\Domain\Services\SectionTree;
use Johncms\Modules\News\Domain\ValueObjects\NewsListFilter;

/**
 * The rows of the list of the news, as news::admin/results draws them: what an article or a
 * section is, where it stands, and what can be done to it.
 *
 * The dates are timestamps of the raw columns, for display_date to show in the time of the
 * visitor; the models format them in a cast of their own.
 */
final readonly class NewsListRowMapper
{
    public function __construct(
        private NewsListControlsMapper $controls,
    ) {
    }

    /**
     * @param Collection<int, NewsArticle> $articles
     * @param string                       $listUrl The page of the list the rows are on, for an
     *                                              action to return to.
     * @return list<array<string, mixed>>
     */
    public function articles(Collection $articles, SectionTree $tree, string $listUrl, ?DateTimeInterface $now = null): array
    {
        $now ??= new DateTimeImmutable();
        $rows = [];

        foreach ($articles as $article) {
            $from = $this->date($article->getRawOriginal('active_from'));
            $to = $this->date($article->getRawOriginal('active_to'));
            $status = ArticleStatus::of((bool) $article->active, $from, $to, $now);
            $section = (int) $article->section_id;
            $editUrl = '/admin/news/articles/' . $article->id . '/edit';

            $rows[] = [
                'id'           => $article->id,
                'name'         => $article->name,
                'code'         => $article->code,
                'edit_url'     => $editUrl,
                'section'      => implode(' › ', $tree->pathOf($section)),
                'section_url'  => $section !== NewsListFilter::ROOT ? $this->controls->url(new NewsListFilter(section: $section)) : '',
                'status'       => $status->value,
                'status_title' => NewsListControlsMapper::statusTitle($status),
                // The date that decides the status, when there is one: when it starts or ended.
                'status_date'  => match ($status) {
                    ArticleStatus::SCHEDULED => $from?->getTimestamp() ?? 0,
                    ArticleStatus::EXPIRED   => $to?->getTimestamp() ?? 0,
                    default                  => 0,
                },
                'author'       => $article->author->name ?? '',
                'author_url'   => $article->author !== null ? '/admin/users/' . $article->author->id : '',
                'views'        => $article->view_count,
                'comments'     => (int) $article->getAttribute('comments_count'),
                'created'      => $this->timestamp($article->getRawOriginal('created_at')),
                'updated'      => $this->timestamp($article->getRawOriginal('updated_at')),
                'actions'      => [
                    $this->action('get', $editUrl, __('Edit'), 'pencil'),
                    $this->action('get', $article->url, __('Open on the site'), 'external-link'),
                    $this->action('get', '/admin/news/articles/' . $article->id . '/delete?back=' . rawurlencode($listUrl), __('Delete'), 'trash-2', danger: true, dialog: true),
                ],
            ];
        }

        return $rows;
    }

    /**
     * @param Collection<int, NewsSection> $sections
     * @return list<array<string, mixed>>
     */
    public function sections(Collection $sections, SectionTree $tree, string $listUrl): array
    {
        $rows = [];

        foreach ($sections as $section) {
            $path = $tree->pathOf($section->id);
            // The sections above it; the name itself is the title of the row.
            array_pop($path);
            $articlesUrl = $this->controls->url(new NewsListFilter(tab: NewsListTab::ARTICLES, section: $section->id));
            $editUrl = '/admin/news/edit_section/' . $section->id;

            $rows[] = [
                'id'           => $section->id,
                'name'         => $section->name,
                'code'         => $section->code,
                'parents'      => implode(' › ', $path),
                'edit_url'     => $editUrl,
                'articles'     => (int) $section->getAttribute('articles_count'),
                'articles_url' => $articlesUrl,
                'created'      => $this->timestamp($section->getRawOriginal('created_at')),
                'updated'      => $this->timestamp($section->getRawOriginal('updated_at')),
                'actions'      => [
                    $this->action('get', $editUrl, __('Edit'), 'pencil'),
                    $this->action('get', $articlesUrl, __('Articles of the section'), 'file-text'),
                    $this->action('get', '/admin/news/articles/new?section=' . $section->id, __('Add article'), 'plus'),
                    $this->action('get', $section->url, __('Open on the site'), 'external-link'),
                    $this->action('get', '/admin/news/sections/' . $section->id . '/delete?back=' . rawurlencode($listUrl), __('Delete'), 'trash-2', danger: true, dialog: true),
                ],
            ];
        }

        return $rows;
    }

    /**
     * @return array<string, mixed>
     */
    private function action(string $method, string $url, string $title, string $icon, bool $danger = false, bool $dialog = false): array
    {
        return ['method' => $method, 'url' => $url, 'title' => $title, 'icon' => $icon, 'danger' => $danger, 'dialog' => $dialog, 'fields' => []];
    }

    private function date(mixed $raw): ?DateTimeImmutable
    {
        if (! is_string($raw) || $raw === '') {
            return null;
        }

        $date = DateTimeImmutable::createFromFormat('Y-m-d H:i:s', $raw);

        return $date === false ? null : $date;
    }

    private function timestamp(mixed $raw): int
    {
        return $this->date($raw)?->getTimestamp() ?? 0;
    }
}
