<?php

declare(strict_types=1);

namespace Johncms\Modules\News\Application\Controllers;

use Johncms\Modules\News\Application\Collections\NewsDiscussionServiceInterface;
use Johncms\Modules\News\Application\Exceptions\NewsDiscussionAccessDeniedException;
use Johncms\Modules\News\Application\Exceptions\NewsDiscussionValidationException;
use Johncms\Modules\News\Application\Exceptions\NewsItemNotFoundException;
use Symfony\Component\HttpFoundation\JsonResponse;

final readonly class VoteController
{
    public function __construct(
        private NewsDiscussionServiceInterface $discussion,
    ) {
    }

    public function add(int $article_id, int $type_vote = 0): JsonResponse
    {
        try {
            $stats = $this->discussion->vote($article_id, match ($type_vote) {
                0 => -1, 1 => 1, default => 0
            });
            return new JsonResponse(['message' => d__('news', 'Your vote is accepted'), 'rating' => $stats->rating, 'voted' => $stats->currentVote]);
        } catch (NewsDiscussionAccessDeniedException $exception) {
            return new JsonResponse(['error' => $exception->getMessage()], 403);
        } catch (NewsItemNotFoundException $exception) {
            return new JsonResponse(['error' => $exception->getMessage()], 404);
        } catch (NewsDiscussionValidationException $exception) {
            return new JsonResponse(['error' => $exception->getMessage()], 422);
        }
    }
}
