<?php

declare(strict_types=1);

namespace Johncms\Modules\Forum\Application\Services;

use Johncms\Auth\CurrentUser;
use Johncms\Modules\Forum\Domain\Models\ForumMessage;
use Johncms\Modules\Forum\Domain\Models\ForumTopic;
use Johncms\Modules\Forum\Domain\Repository\ForumMessageRepositoryInterface;

/**
 * The post a new one in the topic answers, as the form names it. A post of another topic, one the
 * writer cannot see, their own, or one gone while the answer was written answers nothing: the
 * answer is kept without the link rather than refused.
 */
final readonly class ForumAnsweredPosts
{
    public function __construct(
        private ForumMessageRepositoryInterface $messages,
        private CurrentUser $currentUser,
    ) {
    }

    public function find(ForumTopic $topic, int $postId): ?ForumMessage
    {
        if ($postId <= 0) {
            return null;
        }

        // The repository leaves out a hidden post for whoever may not see it.
        $post = $this->messages->findById($postId);
        if ($post === null || $post->topic_id !== $topic->id || $post->user_id === $this->currentUser->id()) {
            return null;
        }

        return $post;
    }
}
