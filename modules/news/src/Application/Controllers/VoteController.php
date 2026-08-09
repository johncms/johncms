<?php

declare(strict_types=1);

namespace Johncms\Modules\News\Application\Controllers;

use Illuminate\Database\Eloquent\ModelNotFoundException;
use Johncms\Modules\News\Domain\Models\NewsArticle;
use Johncms\Users\User;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

final readonly class VoteController
{
    /**
     * Add vote
     *
     * @param User $user
     * @param int $article_id
     * @param bool $type_vote
     */
    public function add(User $user, int $article_id, bool $type_vote = false): Response
    {
        if (! $user->isValid()) {
            return new JsonResponse(['error' => __('The user is not authorized')], Response::HTTP_FORBIDDEN);
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
            return new JsonResponse(
                [
                    'message' => __('Your vote is accepted'),
                    'rating'  => $current_article->rating,
                    'voted'   => $current_article->current_vote,
                ]
            );
        } catch (ModelNotFoundException $exception) {
            return new JsonResponse(['error' => $exception->getMessage()], Response::HTTP_NOT_FOUND);
        }
    }
}
