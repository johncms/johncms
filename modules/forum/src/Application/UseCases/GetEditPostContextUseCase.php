<?php

declare(strict_types=1);

namespace Johncms\Modules\Forum\Application\UseCases;

use Johncms\Modules\Forum\Application\DTO\EditPostContextDTO;
use Johncms\Modules\Forum\Application\Exceptions\EditPostNotFoundException;
use Johncms\Modules\Forum\Domain\Models\ForumSection;
use Johncms\Modules\Forum\Domain\Repository\ForumMessageRepositoryInterface;
use Johncms\Users\User;

final readonly class GetEditPostContextUseCase
{
    public function __construct(
        private ForumMessageRepositoryInterface $messageRepository,
        private User $currentUser,
    ) {
    }

    public function execute(int $messageId, array $forumSettings): EditPostContextDTO
    {
        $message = $this->messageRepository->findById($messageId);
        if ($message === null) {
            throw new EditPostNotFoundException('Message does not exist or has been deleted.');
        }

        $message->loadMissing(['topic.section']);
        if ($message->topic === null || $message->topic->section === null) {
            throw new EditPostNotFoundException('Message topic not found.');
        }

        /** @var ForumSection $section */
        $section = $message->topic->section;

        $effectiveRights = $this->resolveEffectiveRights($message->topic->curators ?? []);

        $upfp = ! empty($forumSettings['upfp']);
        $includeDeleted = $effectiveRights >= 7;
        $totalForPage = $this->messageRepository->countByTopicIdWithComparison(
            topicId: $message->topic_id,
            messageId: $message->id,
            upfp: $upfp,
            includeDeleted: $includeDeleted,
            strict: false,
        );

        $page = (int) ceil($totalForPage / $this->currentUser->config->kmess);
        $page = max(1, $page);

        $posts = $this->messageRepository->countByTopicId($message->topic_id, false);

        return new EditPostContextDTO(
            message: $message,
            topic: $message->topic,
            section: $section,
            effectiveRights: $effectiveRights,
            page: $page,
            posts: $posts,
            backUrl: '/forum/?type=topic&id=' . $message->topic_id . '&page=' . $page,
        );
    }

    /**
     * @param array<int, string> $curators
     */
    private function resolveEffectiveRights(array $curators): int
    {
        if (array_key_exists($this->currentUser->id, $curators)) {
            return 3;
        }

        return $this->currentUser->rights;
    }
}
