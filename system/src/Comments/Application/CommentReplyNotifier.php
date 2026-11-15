<?php

declare(strict_types=1);

namespace Johncms\Comments\Application;

use Johncms\Comments\CommentSubjectDTO;
use Johncms\Notifications\NotificationRepositoryInterface;
use Johncms\Users\IgnoreListCheckerInterface;
use Johncms\Users\User;

/**
 * Tells the author of a comment that somebody answered it, and takes the word back when the answer
 * is deleted.
 *
 * Nobody is told of their own answer, a comment without an account is told nothing, and an author
 * who ignores the one answering does not hear from them here either. The fields are plain text;
 * the template of the event escapes them.
 */
final readonly class CommentReplyNotifier
{
    public const string MODULE = 'comments';
    public const string EVENT = 'reply';

    /** Characters of the answer the notification shows. */
    private const int EXCERPT_LENGTH = 200;

    public function __construct(
        private NotificationRepositoryInterface $notifications,
        private IgnoreListCheckerInterface $ignoreList,
        private CommentExcerpt $excerpt,
    ) {
    }

    /**
     * @param int $recipient The author of the comment answered; 0 when it has no account.
     */
    public function replied(CommentSubjectDTO $subject, int $recipient, int $replyId, string $html, User $author): void
    {
        if ($recipient === 0 || $recipient === $author->id || $this->ignoreList->isBlockedBy($recipient, $author->id)) {
            return;
        }

        $this->notifications->create(
            module: self::MODULE,
            eventType: self::EVENT,
            userId: $recipient,
            senderId: $author->id,
            entityId: $replyId,
            fields: [
                'user_name'     => $author->name,
                'subject_title' => $subject->title,
                'reply_url'     => $subject->discussionUrl . '/comment/' . $replyId,
                'message'       => $this->excerpt->of($html, self::EXCERPT_LENGTH),
            ],
        );
    }

    /**
     * The answers are gone: so are the notifications about them.
     *
     * @param list<int> $commentIds
     */
    public function withdrawn(array $commentIds): void
    {
        if ($commentIds === []) {
            return;
        }

        $this->notifications->deleteForEntities(self::MODULE, self::EVENT, $commentIds);
    }
}
