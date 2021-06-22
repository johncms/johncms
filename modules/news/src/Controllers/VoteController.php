<?php

/**
 * This file is part of JohnCMS Content Management System.
 *
 * @copyright JohnCMS Community
 * @license   https://opensource.org/licenses/GPL-3.0 GPL-3.0
 * @link      https://johncms.com JohnCMS Project
 */

declare(strict_types=1);

namespace News\Controllers;

use Johncms\Controller\BaseController;
use Johncms\Users\User;
use News\Models\NewsArticle;
use News\Utils\Helpers;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class VoteController extends BaseController
{
    protected $module_name = 'news';

    /**
     * Add vote
     *
     * @param User $user
     * @param int $article_id
     * @param bool $type_vote
     */
    public function add(User $user, int $article_id, bool $type_vote = false): void
    {
        if (! $user->isValid()) {
            http_response_code(403);
            Helpers::returnJson(['error' => __('The user is not authorized')]);
        }

        try {
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
            Helpers::returnJson(
                [
                    'message' => __('Your vote is accepted'),
                    'rating'  => $current_article->rating,
                    'voted'   => $current_article->current_vote,
                ]
            );
        } catch (ModelNotFoundException $exception) {
            http_response_code(404);
            Helpers::returnJson(['error' => $exception->getMessage()]);
        }
    }
}
