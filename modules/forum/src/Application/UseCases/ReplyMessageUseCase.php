<?php

declare(strict_types=1);

namespace Johncms\Modules\Forum\Application\UseCases;

use Johncms\Auth\Authorization\CorePermissions;
use Johncms\Auth\CurrentUser;
use Johncms\Modules\Forum\Application\Services\ForumPermissions;
use Johncms\Auth\Authorization\AccessCheckerInterface;
use Johncms\Modules\Forum\Application\DTO\PostMessageResultDTO;
use Johncms\Modules\Forum\Application\Services\ForumTopicStatsRecalculator;
use Johncms\Modules\Forum\Domain\Models\ForumMessage;
use Johncms\Modules\Forum\Domain\Models\ForumTopic;
use Johncms\Modules\Forum\Domain\Repository\ForumMessageRepositoryInterface;
use Johncms\Notifications\Notification;
use Johncms\Smilies\SmiliesRendererInterface;
use Johncms\Security\ClientInfoDTO;

final readonly class ReplyMessageUseCase
{
    public function __construct(
        private ForumMessageRepositoryInterface $messageRepository,
        private ForumTopicStatsRecalculator $topicStatsRecalculator,
        private SmiliesRendererInterface $smiliesRenderer,
        private CurrentUser $currentUser,
        private Notification $notification,
        private AccessCheckerInterface $accessChecker,
    ) {
    }

    public function execute(
        ForumMessage $sourceMessage,
        ForumTopic $topic,
        string $messageText,
        bool $addFiles,
        array $forumSettings,
        ClientInfoDTO $clientInfo,
    ): PostMessageResultDTO {
        $message = new ForumMessage();
        $message->topic_id = $topic->id;
        $message->date = time();
        $message->user_id = $this->currentUser->id();
        $message->user_name = $this->currentUser->user()->name;
        $message->ip = $clientInfo->ip;
        $message->ip_via_proxy = $clientInfo->ipViaProxy;
        $message->user_agent = $clientInfo->userAgent;
        $message->text = $messageText;
        $this->messageRepository->save($message);

        $this->currentUser->user()->update(
            [
                'postforum' => ($this->currentUser->user()->postforum + 1),
                'lastpost'  => time(),
            ]
        );

        $this->topicStatsRecalculator->recalculate($topic->id);

        $this->sendNotification($sourceMessage, $topic, (int) $message->id, $messageText);

        $page = $this->resolveMessagePage($topic->id, $forumSettings);

        return new PostMessageResultDTO(
            messageId: (int) $message->id,
            topicId: $topic->id,
            page: $page,
        );
    }

    private function resolveMessagePage(int $topicId, array $forumSettings): int
    {
        $includeDeleted = $this->accessChecker->allows(ForumPermissions::DELETED_VIEW);
        $total = $this->messageRepository->countByTopicId($topicId, $includeDeleted);
        $page = $forumSettings['upfp'] ? 1 : (int) ceil($total / $this->currentUser->user()->config->kmess);

        return max(1, $page);
    }

    private function sendNotification(ForumMessage $sourceMessage, ForumTopic $topic, int $postId, string $messageText): void
    {
        $previewMessage = strip_tags(trim($messageText));
        $previewMessage = strlen($previewMessage) > 200 ? mb_substr($previewMessage, 0, 200) . '...' : $previewMessage;
        $previewMessage = $this->smiliesRenderer->render(
            $previewMessage,
            $this->accessChecker->allows(CorePermissions::SMILIES_ADMIN_USE)
        );

        $this->notification->create(
            [
                'module'     => 'forum',
                'event_type' => 'new_message',
                'user_id'    => $sourceMessage->user_id,
                'sender_id'  => $this->currentUser->id(),
                'entity_id'  => $postId,
                'fields'     => [
                    'topic_name'       => htmlspecialchars($topic->name),
                    'user_name'        => htmlspecialchars($this->currentUser->user()->name),
                    'topic_url'        => $topic->url,
                    'reply_to_message' => '/forum/post/' . $sourceMessage->id . '/',
                    'message'          => $previewMessage,
                    'post_id'          => $postId,
                    'topic_id'         => $topic->id,
                ],
            ]
        );
    }
}
