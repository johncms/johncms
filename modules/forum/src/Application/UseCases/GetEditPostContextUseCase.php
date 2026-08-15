<?php

declare(strict_types=1);

namespace Johncms\Modules\Forum\Application\UseCases;

use Johncms\Auth\Authorization\AccessCheckerInterface;
use Johncms\Auth\CurrentUser;
use Johncms\Modules\Forum\Application\DTO\EditPostContextDTO;
use Johncms\Modules\Forum\Application\Exceptions\ForumNotFoundException;
use Johncms\Modules\Forum\Application\Services\ForumPermissions;
use Johncms\Modules\Forum\Application\Services\ForumTopicPathService;
use Johncms\Modules\Forum\Domain\Models\ForumSection;
use Johncms\Modules\Forum\Domain\Repository\ForumMessageRepositoryInterface;

final readonly class GetEditPostContextUseCase
{
    public function __construct(
        private ForumMessageRepositoryInterface $messageRepository,
        private ForumTopicPathService $topicPathService,
        private CurrentUser $currentUser,
        private AccessCheckerInterface $accessChecker,
    ) {
    }

    public function execute(int $messageId, array $forumSettings): EditPostContextDTO
    {
        $message = $this->messageRepository->findById($messageId);
        if ($message === null) {
            throw new ForumNotFoundException('Message does not exist or has been deleted.');
        }

        $message->loadMissing(['topic.section']);
        if ($message->topic === null || $message->topic->section === null) {
            throw new ForumNotFoundException('Message topic not found.');
        }

        /** @var ForumSection $section */
        $section = $message->topic->section;

        // The curator of the topic moderates it: the topic is what the permission is asked
        // about, and the voter of the module answers for them.
        $canModerate = $this->accessChecker->allows(ForumPermissions::TOPIC_MODERATE, $message->topic);

        $upfp = ! empty($forumSettings['upfp']);
        $includeDeleted = $this->accessChecker->allows(ForumPermissions::DELETED_VIEW);
        $totalForPage = $this->messageRepository->countByTopicIdWithComparison(
            topicId: $message->topic_id,
            messageId: $message->id,
            upfp: $upfp,
            includeDeleted: $includeDeleted,
            strict: false,
        );

        $page = (int) ceil($totalForPage / $this->currentUser->user()->config->kmess);
        $page = max(1, $page);

        $posts = $this->messageRepository->countByTopicId($message->topic_id, false);

        return new EditPostContextDTO(
            message: $message,
            topic: $message->topic,
            section: $section,
            canModerate: $canModerate,
            page: $page,
            posts: $posts,
            backUrl: $this->topicPathService->getTopicUrl($message->topic, $page > 1 ? $page : null),
        );
    }
}
