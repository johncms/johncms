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
            ->orderByDesc('id');

        if (! empty($sections)) {
            $articles->whereIn('section_id', $sections);
        }

        return $articles->paginate();
    }
}
