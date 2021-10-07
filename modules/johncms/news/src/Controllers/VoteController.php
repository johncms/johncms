<?php

/**
 * This file is part of JohnCMS Content Management System.
 *
 * @copyright JohnCMS Community
 * @license   https://opensource.org/licenses/GPL-3.0 GPL-3.0
 * @link      https://johncms.com JohnCMS Project
 */

declare(strict_types=1);

namespace Johncms\News\Controllers;

use Johncms\Controller\BaseController;
use Johncms\Http\JsonResponse;
use Johncms\News\Models\NewsArticle;
use Johncms\Users\User;
use Psr\Http\Message\ResponseInterface;

class VoteController extends BaseController
{
    protected string $moduleName = 'johncms/news';

    /**
     * Add vote
     *
     * @param int $article_id
     * @param bool $type_vote
     * @param User|null $user
     * @return array|ResponseInterface
     */
    public function add(int $article_id, bool $type_vote = false, ?User $user = null): array|ResponseInterface
    {
        if (! $user) {
            return new JsonResponse(['error' => __('The user is not authorized')], 403);
        }

        $current_article = (new NewsArticle())->findOrFail($article_id);
        $current_article->votes()->updateOrCreate(
            [
                'user_id' => $user->id,
            ],
            [
                'vote' => $type_vote ? 1 : -1,
            ]
        );
        $current_article->loadSum('votes', 'vote');
        return [
            'message' => __('Your vote is accepted'),
            'rating'  => $current_article->rating,
            'voted'   => $current_article->current_vote,
        ];
    }
}
