<?php

declare(strict_types=1);

namespace News\Actions;

use News\Models\NewsArticle;
use News\Utils\AbstractAction;
use News\Utils\Helpers;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class Vote extends AbstractAction
{
    /**
     * Add vote
     */
    public function add(): void
    {
        $article_id = $this->request->getQuery('article_id', 0, FILTER_VALIDATE_INT);
        $type_vote = $this->request->getQuery('type_vote', 1, FILTER_VALIDATE_INT);

        if (! $this->user->isValid()) {
            http_response_code(403);
            Helpers::returnJson(['error' => __('The user is not authorized')]);
        }

        try {
            $current_article = (new NewsArticle())->findOrFail($article_id);
            $current_article->votes()->updateOrCreate(
                [
                    'user_id' => $this->user->id,
                ],
                [
                    'vote' => $type_vote === 1 ? 1 : -1,
                ]
            );
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
