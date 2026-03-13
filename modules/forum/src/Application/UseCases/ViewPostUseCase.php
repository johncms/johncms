<?php

declare(strict_types=1);

namespace Johncms\Modules\Forum\Application\UseCases;

use Johncms\Modules\Forum\Application\DTO\PostActionsDTO;
use Johncms\Modules\Forum\Application\DTO\PostAuthorDTO;
use Johncms\Modules\Forum\Application\DTO\PostEditInfoDTO;
use Johncms\Modules\Forum\Application\DTO\PostFileDTO;
use Johncms\Modules\Forum\Application\DTO\PostModerationDTO;
use Johncms\Modules\Forum\Application\DTO\ViewPostDTO;
use Johncms\Modules\Forum\Application\Exceptions\AccessDeniedException;
use Johncms\Modules\Forum\Domain\Exceptions\MessageNotFoundException;
use Johncms\Modules\Forum\Domain\Models\ForumFile;
use Johncms\Modules\Forum\Domain\Models\ForumMessage;
use Johncms\Modules\Forum\Domain\Repository\ForumMessageRepositoryInterface;
use Johncms\System\Legacy\Tools;
use Johncms\Users\User;

final readonly class ViewPostUseCase
{
    public function __construct(
        private ForumMessageRepositoryInterface $messageRepository,
        private User $currentUser,
        private Tools $tools,
    ) {
    }

    /**
     * @return array{post: ViewPostDTO, topic: mixed, canonical: string}
     */
    public function execute(int $postId, int $start, array $forumSettings, string $homeUrl): array
    {
        $message = $this->messageRepository->findById($postId);
        if ($message === null) {
            throw new MessageNotFoundException(sprintf('Message with id "%s" could not be found.', $postId));
        }

        if ($this->currentUser->rights < 7 && $message->deleted) {
            throw new AccessDeniedException(sprintf('Access denied to message with id "%d".', $postId));
        }

        $message->loadMissing(['files', 'topic']);

        $userData = $message->user_data;
        $author = new PostAuthorDTO(
            id: $message->user_id ?: null,
            name: $message->user_name ?? '',
            avatarUrl: '',
            isOnline: $userData->is_online ?? false,
            status: $userData->status ?? null,
            profileUrl: $this->getProfileUrl($message),
            rightsName: $userData->rights_name ?? null
        );

        $editInfo = null;
        if (! empty($message->edit_count)) {
            $editTime = (int) $message->getRawOriginal('edit_time');
            $editInfo = new PostEditInfoDTO(
                editorName: $message->editor_name,
                editedAt: $editTime ? $this->tools->displayDate($editTime) : '',
                editCount: (int) $message->edit_count
            );
        }

        $files = $this->getFiles($message);
        $moderation = $this->getModerationInfo($message);
        $actions = $this->getActions($message->id, $message->user_id, $start);

        $page = $this->getMessagePage($message, $forumSettings);
        $backToTopicUrl = '/forum/?type=topic&id=' . $message->topic_id . '&page=' . $page;

        $canonical = $homeUrl . '/forum/?type=topic&id=' . $message->topic_id;
        if ($page > 1) {
            $canonical .= '&page=' . $page;
        }

        $post = new ViewPostDTO(
            id: $message->id,
            isDeleted: (bool) $message->deleted,
            body: $message->post_text,
            createdAt: $this->tools->displayDate((int) $message->date),
            author: $author,
            editInfo: $editInfo,
            files: $files,
            moderation: $moderation,
            actions: $actions,
            backToTopicUrl: $backToTopicUrl,
            forumUrl: '/forum/'
        );

        return [
            'post'      => $post,
            'topic'     => $message->topic,
            'canonical' => $canonical,
        ];
    }

    /**
     * @return PostFileDTO[]
     */
    private function getFiles(ForumMessage $message): array
    {
        $files = [];
        foreach ($message->files as $file) {
            if (! $file instanceof ForumFile) {
                continue;
            }

            $file->getFileInfo();
            if (! $file->file_info?->isFile()) {
                continue;
            }

            $files[] = new PostFileDTO(
                filename: $file->filename,
                fileUrl: $file->file_url,
                previewUrl: $file->file_preview ?: null,
                fileSize: $file->file_size,
                downloadCount: (int) $file->dlcount
            );
        }

        return $files;
    }

    private function getModerationInfo(ForumMessage $message): ?PostModerationDTO
    {
        if ($this->currentUser->rights < 1) {
            return null;
        }

        $ip = (string) $message->ip;
        $ipViaProxy = (string) $message->ip_via_proxy;
        $ipViaProxy = $ipViaProxy !== '' ? $ipViaProxy : null;

        return new PostModerationDTO(
            ip: $ip,
            ipViaProxy: $ipViaProxy,
            searchIpUrl: '/admin/search_ip/?ip=' . $ip,
            searchIpViaProxyUrl: $ipViaProxy ? '/admin/search_ip/?ip=' . $ipViaProxy : null,
            userAgent: $message->user_agent
        );
    }

    private function getActions(int $postId, ?int $authorId, int $start): PostActionsDTO
    {
        $replyUrl = null;
        $quoteUrl = null;

        if ($this->currentUser->isValid() && $authorId !== null && $this->currentUser->id !== $authorId) {
            $replyUrl = '/forum/?act=say&amp;type=reply&amp;id=' . $postId . '&amp;start=' . $start;
            $quoteUrl = '/forum/?act=say&amp;type=reply&amp;id=' . $postId . '&amp;start=' . $start . '&amp;cyt';
        }

        return new PostActionsDTO($replyUrl, $quoteUrl);
    }

    private function getProfileUrl(ForumMessage $message): string
    {
        if (! $this->currentUser->isValid() || $message->user_id === null) {
            return '';
        }

        if ($this->currentUser->id === $message->user_id) {
            return '';
        }

        return '/profile/?user=' . $message->user_id;
    }

    private function getMessagePage(ForumMessage $message, array $forumSettings): int
    {
        $countQuery = ForumMessage::query()->where('topic_id', $message->topic_id);
        if (! empty($forumSettings['upfp'])) {
            $countQuery->where('id', '>=', $message->id);
        } else {
            $countQuery->where('id', '<=', $message->id);
        }

        $total = (int) $countQuery->count();
        $page = (int) ceil($total / $this->currentUser->config->kmess);

        return max(1, $page);
    }
}
