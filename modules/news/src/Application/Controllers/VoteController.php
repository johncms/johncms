<?php

declare(strict_types=1);

namespace Johncms\Modules\News\Application\Controllers;

use Illuminate\Database\Eloquent\ModelNotFoundException;
use Johncms\Http\Controller\ControllerContext;
use Johncms\Modules\News\Application\Utils\Helpers;
use Johncms\Modules\News\Domain\Models\NewsArticle;
use Johncms\Users\User;

final readonly class VoteController
{
    public function __construct(
        private ControllerContext $controllerContext,
    ) {
        $this->controllerContext->initModule('news');
    }

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
