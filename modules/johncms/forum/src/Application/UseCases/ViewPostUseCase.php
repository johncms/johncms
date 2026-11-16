<?php

declare(strict_types=1);

namespace Johncms\Modules\Forum\Application\UseCases;

use Johncms\Auth\Authorization\AccessCheckerInterface;
use Johncms\Auth\CurrentUser;
use Johncms\Modules\Forum\Application\DTO\ForumPostDTO;
use Johncms\Modules\Forum\Application\Exceptions\ForumAccessDeniedException;
use Johncms\Modules\Forum\Application\Exceptions\ForumNotFoundException;
use Johncms\Modules\Forum\Application\Services\ForumPermissions;
use Johncms\Modules\Forum\Application\Services\ForumPostRows;
use Johncms\Modules\Forum\Application\Services\ForumTopicPathService;
use Johncms\Modules\Forum\Application\Services\ForumUserSettings;
use Johncms\Modules\Forum\Domain\Models\ForumMessage;
use Johncms\Modules\Forum\Domain\Models\ForumTopic;
use Johncms\Modules\Forum\Domain\Repository\ForumMessageRepositoryInterface;

final readonly class ViewPostUseCase
{
    public function __construct(
        private ForumMessageRepositoryInterface $messageRepository,
        private ForumTopicPathService $topicPathService,
        private CurrentUser $currentUser,
        private AccessCheckerInterface $accessChecker,
        private ForumPostRows $postRows,
        private ForumUserSettings $userSettings,
    ) {
    }

    /**
     * @return array{post: ForumPostDTO, topic: ForumTopic, canonical: string, topic_url: string}
     */
    public function execute(int $postId, string $homeUrl): array
    {
        $message = $this->messageRepository->findById($postId);
        if ($message === null || $message->topic === null) {
            throw new ForumNotFoundException(sprintf('Message with id "%s" could not be found.', $postId));
        }

        if ($message->deleted && ! $this->accessChecker->allows(ForumPermissions::DELETED_VIEW)) {
            throw new ForumAccessDeniedException(sprintf('Access denied to message with id "%d".', $postId));
        }

        $topic = $message->topic;
        $page = $this->getMessagePage($message);
        $canReply = $this->currentUser->isValid()
            && (! $topic->closed || $this->accessChecker->allows(ForumPermissions::TOPIC_MODERATE, $topic));

        [$post] = $this->postRows->present(
            messages: [$message],
            editableIds: [],
            canReply: $canReply,
            page: $page,
        );

        return [
            'post'      => $post,
            'topic'     => $topic,
            'canonical' => $homeUrl . $this->topicPathService->getTopicUrl($topic, $page > 1 ? $page : null),
            'topic_url' => $this->topicPathService->getTopicUrl($topic, $page) . '#post-' . $message->id,
        ];
    }

    private function getMessagePage(ForumMessage $message): int
    {
        $countQuery = ForumMessage::query()->where('topic_id', $message->topic_id);
        if ($this->userSettings->newestFirst()) {
            $countQuery->where('id', '>=', $message->id);
        } else {
            $countQuery->where('id', '<=', $message->id);
        }

        $total = (int) $countQuery->count();
        $page = (int) ceil($total / $this->currentUser->user()->config->kmess);

        return max(1, $page);
    }
}
