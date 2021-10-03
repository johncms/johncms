<?php

/**
 * This file is part of JohnCMS Content Management System.
 *
 * @copyright JohnCMS Community
 * @license   https://opensource.org/licenses/GPL-3.0 GPL-3.0
 * @link      https://johncms.com JohnCMS Project
 */

declare(strict_types=1);

namespace Johncms\News;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Johncms\Http\Session;
use Johncms\NavChain;
use Johncms\News\Models\NewsArticle;

class Article
{
    protected NavChain $nav_chain;

    public function __construct()
    {
        $this->nav_chain = di(NavChain::class);
    }

    public function getArticles(array $sections = []): LengthAwarePaginator
    {
        $articles = (new NewsArticle())
            ->active()
            ->withCount('comments')
            ->withSum('votes', 'vote')
            ->orderByDesc('id');

        if (! empty($sections)) {
            $articles->whereIn('section_id', $sections);
        }

        return $articles->paginate();
    }

    public function getArticle(string $article_code): NewsArticle
    {
        $article = (new NewsArticle())
            ->withSum('votes', 'vote')
            ->where('code', $article_code)
            ->firstOrFail();

        $session = di(Session::class);

        // Фиксируем количество просмотров
        if (! in_array($article->id, (array) $session->get('news_viewed_articles', []), true)) {
            ++$article->view_count;
            $article->save();
            $viewed_articles = $session->get('news_viewed_articles', []);
            $viewed_articles[] = $article->id;
            $session->set('news_viewed_articles', $viewed_articles);
        }
        $this->nav_chain->add($article->name, $article->url);
        return $article;
    }
}
