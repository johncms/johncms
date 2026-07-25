<?php

declare(strict_types=1);

namespace Johncms\Modules\Forum\Application\UseCases;

use Johncms\Modules\Forum\Application\DTO\PostMessageResultDTO;
use Johncms\Modules\Forum\Application\Services\ForumTopicStatsRecalculator;
use Johncms\Modules\Forum\Domain\Models\ForumMessage;
use Johncms\Modules\Forum\Domain\Models\ForumTopic;
use Johncms\Modules\Forum\Domain\Models\ForumUnread;
use Johncms\Modules\Forum\Domain\Repository\ForumFileRepositoryInterface;
use Johncms\Modules\Forum\Domain\Repository\ForumMessageRepositoryInterface;
use Johncms\Http\Environment;
use Johncms\Users\User;

final readonly class PostMessageUseCase
{
    public function __construct(
        private ForumMessageRepositoryInterface $messageRepository,
        private ForumTopicStatsRecalculator $topicStatsRecalculator,
        private ForumFileRepositoryInterface $fileRepository,
        private Environment $environment,
        private User $currentUser,
    ) {
    }

    public function execute(
        ForumTopic $topic,
        string $messageText,
        bool $addFiles,
        array $forumSettings,
    ): PostMessageResultDTO {
        $messageId = null;
        if (! $addFiles) {
            $messageId = $this->tryMergeWithLastMessage($topic, $messageText);
        }

        if ($messageId === null) {
            $messageId = $this->insertMessage($topic, $messageText);
        }

        $this->topicStatsRecalculator->recalculate($topic->id);

        $this->currentUser->update(
            [
                'postforum' => ($this->currentUser->postforum + 1),
                'lastpost'  => time(),
            ]
        );

        if ($addFiles) {
            ForumUnread::query()->updateOrInsert(
                ['topic_id' => $topic->id, 'user_id' => $this->currentUser->id],
                ['time' => time()]
            );
        }

        $page = $this->resolveMessagePage($topic->id, $forumSettings);

        return new PostMessageResultDTO(
            messageId: (int) $messageId,
            topicId: $topic->id,
            page: $page,
        );
    }

    private function insertMessage(ForumTopic $topic, string $messageText): int
    {
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

        return (int) $message->id;
    }

    private function tryMergeWithLastMessage(ForumTopic $topic, string $messageText): ?int
    {
        $lastMessage = $this->messageRepository->findLastMessageInTopic(
            $topic->id,
            $this->currentUser->rights >= 7
        );

        if ($lastMessage === null) {
            return null;
        }

        if ((int) $lastMessage->user_id !== $this->currentUser->id) {
            return null;
        }

        $hasFiles = $this->fileRepository->hasFilesForPost((int) $lastMessage->id);
        if ($hasFiles) {
            return null;
        }

        $rawText = (string) $lastMessage->getRawOriginal('text');
        $lastTextLength = strlen($rawText);
        if (($lastMessage->date + 3600) < time() || $lastTextLength + strlen($messageText) >= 65536) {
            return null;
        }

        $newText = $rawText;
        if (strpos($newText, '<small class="gray date">') === false) {
            $newText = '<small class="gray">' . date('d.m.Y H:i', (int) $lastMessage->date) . '</small>' . PHP_EOL . $newText;
        }

        $newText .= PHP_EOL . PHP_EOL . '<small class="gray date">' . date('d.m.Y H:i', time()) . '</small>' . PHP_EOL . $messageText;

        $lastMessage->text = $newText;
        $lastMessage->date = time();
        $this->messageRepository->save($lastMessage);

        return (int) $lastMessage->id;
    }

    private function resolveMessagePage(int $topicId, array $forumSettings): int
    {
        $includeDeleted = $this->currentUser->rights >= 7;
        $total = $this->messageRepository->countByTopicId($topicId, $includeDeleted);
        $page = $forumSettings['upfp'] ? 1 : (int) ceil($total / $this->currentUser->config->kmess);

        return max(1, $page);
    }
}
