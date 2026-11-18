<?php

declare(strict_types=1);

namespace Johncms\Modules\Forum\Application\UseCases;

use Johncms\Modules\Forum\Application\Exceptions\ForumAccessDeniedException;
use Johncms\Modules\Forum\Application\Exceptions\ForumNotFoundException;
use Johncms\Modules\Forum\Domain\Models\ForumTopic;
use Johncms\Modules\Forum\Domain\Repository\ForumTopicRepositoryInterface;
use Johncms\Auth\Authorization\AccessCheckerInterface;
use Johncms\Modules\Forum\Application\Services\ForumPermissions;

final readonly class GetDeleteTopicContextUseCase
{
    public function __construct(
        private ForumTopicRepositoryInterface $topicRepository,
        private AccessCheckerInterface $accessChecker,
    ) {
    }

    /**
     * Hiding a topic is moderation; deleting it for good is a permission of its own, which may be
     * granted without moderation, so it lets the request in on its own.
     */
    public function execute(int $topicId, bool $forGood): ForumTopic
    {
        $allowed = $this->accessChecker->allows(ForumPermissions::TOPIC_MODERATE)
            || ($forGood && $this->accessChecker->allows(ForumPermissions::TOPIC_DESTROY));
        if (! $allowed) {
            throw new ForumAccessDeniedException('Access denied to delete topic.');
        }

        $topic = $this->topicRepository->findById($topicId);
        if ($topic === null) {
            throw new ForumNotFoundException('Topic not found.');
        }

        return $topic;
    }
}
