<?php

declare(strict_types=1);

namespace Johncms\Modules\Forum\Application\Services;

use Johncms\Auth\Authorization\AccessCheckerInterface;
use Johncms\Auth\Authorization\CorePermissions;
use Johncms\Auth\Authorization\StaffTitles;
use Johncms\Auth\CurrentUser;
use Johncms\Content\ContentContext;
use Johncms\Content\ContentRendererInterface;
use Johncms\Modules\Forum\Application\DTO\ForumPostDTO;
use Johncms\Modules\Forum\Application\DTO\PostActionsDTO;
use Johncms\Modules\Forum\Application\DTO\PostAuthorDTO;
use Johncms\Modules\Forum\Application\DTO\PostEditInfoDTO;
use Johncms\Modules\Forum\Application\DTO\PostFileDTO;
use Johncms\Modules\Forum\Application\DTO\PostModerationDTO;
use Johncms\Modules\Forum\Domain\Models\ForumFile;
use Johncms\Modules\Forum\Domain\Models\ForumMessage;
use Johncms\Users\ProfileLinksInterface;
use Johncms\Utils\DateFormatterInterface;

/**
 * The posts of a topic as the visitor sees them: the text rendered, the author, the files and what
 * may be done with each.
 *
 * Takes the posts with the columns of the author joined (ForumMessage::scopeUsers) and the files
 * loaded; the roles of all the authors are read at once.
 */
final readonly class ForumPostRows
{
    /** A post is online for this long after the last visit of its author. */
    private const int ONLINE_SECONDS = 300;

    public function __construct(
        private CurrentUser $currentUser,
        private AccessCheckerInterface $accessChecker,
        private StaffTitles $staffTitles,
        private ContentRendererInterface $contentRenderer,
        private ProfileLinksInterface $profileLinks,
        private DateFormatterInterface $dateFormatter,
    ) {
    }

    /**
     * @param iterable<ForumMessage> $messages
     * @param array<int, true>       $editableIds The posts the visitor may change, deleting them included.
     * @param bool                   $canReply    The visitor writes in the topic: they may answer the posts of others.
     * @param int                    $page        The page of the topic the posts are on; the answer returns there.
     * @return list<ForumPostDTO>
     */
    public function present(iterable $messages, array $editableIds, bool $canReply, int $page): array
    {
        $messages = is_array($messages) ? $messages : iterator_to_array($messages, false);
        $this->staffTitles->preload(array_map(static fn (ForumMessage $message): int => (int) $message->user_id, $messages));

        $seesOrigin = $this->accessChecker->allows(CorePermissions::USERS_ORIGIN_VIEW);
        $seesHidden = $this->accessChecker->allows(ForumPermissions::DELETED_VIEW);

        $posts = [];
        foreach ($messages as $message) {
            $posts[] = $this->post(
                message: $message,
                canEdit: isset($editableIds[$message->id]),
                canReply: $canReply,
                page: $page,
                seesOrigin: $seesOrigin,
                seesHidden: $seesHidden,
            );
        }

        return $posts;
    }

    private function post(ForumMessage $message, bool $canEdit, bool $canReply, int $page, bool $seesOrigin, bool $seesHidden): ForumPostDTO
    {
        $authorId = (int) $message->user_id;
        $deleted = $message->deleted;

        return new ForumPostDTO(
            id: $message->id,
            url: '/forum/post/' . $message->id . '/',
            body: $this->contentRenderer->render(
                (string) $message->text,
                new ContentContext(adminSmilies: $this->staffTitles->isStaff($authorId))
            ),
            createdAt: $this->dateFormatter->format((int) $message->date),
            author: $this->author($message, $authorId),
            editInfo: $this->editInfo($message),
            files: $this->files($message, $canEdit),
            isDeleted: $deleted,
            deletedBy: $deleted ? $message->deleted_by : '',
            restoredBy: ! $deleted && $seesHidden ? $message->deleted_by : '',
            origin: $seesOrigin
                ? new PostModerationDTO(
                    ip: (string) $message->ip,
                    ipViaProxy: (string) $message->ip_via_proxy,
                    userAgent: (string) $message->user_agent,
                )
                : null,
            actions: $this->actions($message->id, $authorId, $canEdit, $canReply, $page, $deleted && $seesHidden),
        );
    }

    private function author(ForumMessage $message, int $authorId): PostAuthorDTO
    {
        if ($authorId === 0) {
            return new PostAuthorDTO(
                id: null,
                name: $message->user_name,
                isOnline: false,
                status: '',
                profileUrl: null,
                rightsName: '',
            );
        }

        return new PostAuthorDTO(
            id: $authorId,
            name: $message->user_name,
            isOnline: (int) $message->getAttribute('lastdate') + self::ONLINE_SECONDS >= time(),
            status: (string) $message->getAttribute('status'),
            profileUrl: $this->profileLinks->urlOf($authorId),
            rightsName: $this->staffTitles->titleFor($authorId),
        );
    }

    private function editInfo(ForumMessage $message): ?PostEditInfoDTO
    {
        if ($message->edit_count === 0) {
            return null;
        }

        // The cast of the column turns it into a formatted string; the time is taken as stored.
        $editedAt = (int) $message->getRawOriginal('edit_time');

        return new PostEditInfoDTO(
            editorName: $message->editor_name,
            editedAt: $editedAt > 0 ? $this->dateFormatter->format($editedAt) : '',
            editCount: $message->edit_count,
        );
    }

    /**
     * @return list<PostFileDTO>
     */
    private function files(ForumMessage $message, bool $canEdit): array
    {
        $files = [];
        foreach ($message->files as $file) {
            if (! $file instanceof ForumFile || ! $file->exists()) {
                continue;
            }

            $files[] = new PostFileDTO(
                filename: (string) $file->filename,
                fileUrl: $file->file_url,
                previewUrl: $file->file_preview ?: null,
                fileSize: $file->file_size,
                downloadCount: (int) $file->dlcount,
                deleteUrl: $canEdit ? $file->delete_url : null,
            );
        }

        return $files;
    }

    private function actions(int $id, int $authorId, bool $canEdit, bool $canReply, int $page, bool $restorable): PostActionsDTO
    {
        $replies = $canReply && $authorId > 0 && $authorId !== $this->currentUser->id();
        $reply = '/forum/reply-message/' . $id . '/';
        $pageQuery = $page > 1 ? 'page=' . $page : '';

        return new PostActionsDTO(
            replyUrl: $replies ? $reply . ($pageQuery !== '' ? '?' . $pageQuery : '') : null,
            quoteUrl: $replies ? $reply . '?' . ($pageQuery !== '' ? $pageQuery . '&' : '') . 'quote=1' : null,
            editUrl: $canEdit ? '/forum/edit-post/' . $id . '/' : null,
            deleteUrl: $canEdit ? '/forum/delete-post/' . $id . '/' : null,
            restoreUrl: $canEdit && $restorable ? '/forum/restore-post/' . $id . '/' : null,
        );
    }
}
