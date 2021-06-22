<?php

/**
 * This file is part of JohnCMS Content Management System.
 *
 * @copyright JohnCMS Community
 * @license   https://opensource.org/licenses/GPL-3.0 GPL-3.0
 * @link      https://johncms.com JohnCMS Project
 */

declare(strict_types=1);

namespace News;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Johncms\Exceptions\PageNotFoundException;
use Johncms\NavChain;
use News\Models\NewsArticle;

class Article
{
    /** @var NavChain */
    protected $nav_chain;

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
