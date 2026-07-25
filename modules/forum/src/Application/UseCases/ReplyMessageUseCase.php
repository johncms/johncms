<?php

declare(strict_types=1);

namespace Johncms\Modules\Forum\Application\UseCases;

use Johncms\Modules\Forum\Application\DTO\PostMessageResultDTO;
use Johncms\Modules\Forum\Application\Services\ForumTopicStatsRecalculator;
use Johncms\Modules\Forum\Domain\Models\ForumMessage;
use Johncms\Modules\Forum\Domain\Models\ForumTopic;
use Johncms\Modules\Forum\Domain\Repository\ForumMessageRepositoryInterface;
use Johncms\Notifications\Notification;
use Johncms\Smilies\SmiliesRendererInterface;
use Johncms\Http\Environment;
use Johncms\Users\User;

final readonly class ReplyMessageUseCase
{
    public function __construct(
        private ForumMessageRepositoryInterface $messageRepository,
        private ForumTopicStatsRecalculator $topicStatsRecalculator,
        private SmiliesRendererInterface $smiliesRenderer,
        private Environment $environment,
        private User $currentUser,
        private Notification $notification,
    ) {
    }

    public function execute(
        ForumMessage $sourceMessage,
        ForumTopic $topic,
        string $messageText,
        bool $addFiles,
        array $forumSettings,
    ): PostMessageResultDTO {
        $message = new ForumMessage();
        $message->topic_id = $topic->id;
        $message->date = time();
        $message->user_id = $this->currentUser->id;
        $message->user_name = $this->currentUser->name;
        $message->ip = $this->environment->getIp(false);
        $message->ip_via_proxy = $this->environment->getIpViaProxy(false);
        $message->user_agent = $this->environment->getUserAgent();
        $message->text = $messageText;
        $this->messageRepository->save($message);

        $this->currentUser->update(
            [
                'postforum' => ($this->currentUser->postforum + 1),
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
        $includeDeleted = $this->currentUser->rights >= 7;
        $total = $this->messageRepository->countByTopicId($topicId, $includeDeleted);
        $page = $forumSettings['upfp'] ? 1 : (int) ceil($total / $this->currentUser->config->kmess);

        return max(1, $page);
    }

    private function sendNotification(ForumMessage $sourceMessage, ForumTopic $topic, int $postId, string $messageText): void
    {
        $previewMessage = strip_tags(trim($messageText));
        $previewMessage = strlen($previewMessage) > 200 ? mb_substr($previewMessage, 0, 200) . '...' : $previewMessage;
        $previewMessage = $this->smiliesRenderer->render($previewMessage, ($this->currentUser->rights > 0));

        $this->notification->create(
            [
                'module'     => 'forum',
                'event_type' => 'new_message',
                'user_id'    => $sourceMessage->user_id,
                'sender_id'  => $this->currentUser->id,
                'entity_id'  => $postId,
                'fields'     => [
                    'topic_name'       => htmlspecialchars($topic->name),
                    'user_name'        => htmlspecialchars($this->currentUser->name),
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
