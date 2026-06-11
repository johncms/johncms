<?php

/**
 * This file is part of JohnCMS Content Management System.
 *
 * @copyright JohnCMS Community
 * @license   https://opensource.org/licenses/GPL-3.0 GPL-3.0
 * @link      https://johncms.com JohnCMS Project
 */

declare(strict_types=1);

namespace Johncms\Modules\News\Application;

use Illuminate\Database\Eloquent\Collection;
use Johncms\Exceptions\PageNotFoundException;
use Johncms\Modules\News\Domain\Models\NewsArticle;
use Johncms\NavChain;

class Article
{
    /** @var NavChain */
    protected $nav_chain;

    public function __construct()
    {
        $this->nav_chain = di(NavChain::class);
    }

    public function countArticles(array $sections = []): int
    {
        $articles = (new NewsArticle())->active();

        if (! empty($sections)) {
            $articles->whereIn('section_id', $sections);
        }

        return $articles->count();
    }

    /**
     * @return Collection<int, NewsArticle>
     */
    public function getArticles(array $sections, int $limit, int $offset): Collection
    {
        $articles = (new NewsArticle())
            ->active()
            ->withCount('comments')
            ->withSum('votes', 'vote')
            ->orderByDesc('id');

        if (! empty($sections)) {
            $articles->whereIn('section_id', $sections);
        }

        return $articles->offset($offset)->limit($limit)->get();
    }

    public function getArticle(string $article_code): NewsArticle
    {
        $article = (new NewsArticle())
            ->withSum('votes', 'vote')
            ->where('code', $article_code)
            ->first();
        if ($article === null) {
            throw new PageNotFoundException(__('The requested article was not found.'));
        }
        // Фиксируем количество просмотров
        if (empty($_SESSION['news_viewed_articles']) || ! in_array($article->id, $_SESSION['news_viewed_articles'], true)) {
            ++$article->view_count;
            $article->save();
            $_SESSION['news_viewed_articles'][] = $article->id;
        }
        $this->nav_chain->add($article->name, $article->url);
        return $article;
    }
}
