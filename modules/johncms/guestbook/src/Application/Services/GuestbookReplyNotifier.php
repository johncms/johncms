<?php

declare(strict_types=1);

namespace Johncms\Modules\Guestbook\Application\Services;

use Johncms\Comments\Application\CommentExcerpt;
use Johncms\Modules\Guestbook\Domain\Models\GuestbookEntry;
use Johncms\Notifications\NotificationRepositoryInterface;
use Johncms\Users\IgnoreListCheckerInterface;

/**
 * Tells the author of an entry that somebody answered it, and takes the word back when the answer
 * is deleted.
 *
 * A guest is answered by nobody: there is no account to tell. A guest who answers is heard like
 * anyone else, without a sender. Nobody is told of their own answer, and an author who ignores the
 * one answering does not hear from them here either. The fields are plain text; the template of
 * the event escapes them.
 */
final readonly class GuestbookReplyNotifier
{
    public const string MODULE = 'guestbook';
    public const string EVENT = 'reply';

    /** Characters of the answer the notification shows. */
    private const int EXCERPT_LENGTH = 200;

    public function __construct(
        private NotificationRepositoryInterface $notifications,
        private IgnoreListCheckerInterface $ignoreList,
        private CommentExcerpt $excerpt,
    ) {
    }

    public function replied(GuestbookEntry $answered, GuestbookEntry $answer): void
    {
        $recipient = (int) $answered->user_id;
        $sender = (int) $answer->user_id;
        if ($recipient === 0 || $recipient === $sender || ($sender !== 0 && $this->ignoreList->isBlockedBy($recipient, $sender))) {
            return;
        }

        $this->notifications->create(
            module: self::MODULE,
            eventType: self::EVENT,
            userId: $recipient,
            senderId: $sender,
            entityId: $answer->id,
            fields: [
                // The model hands the name out escaped; the template of the event escapes the stored one.
                'user_name'  => (string) $answer->getRawOriginal('name'),
                'admin_club' => $answer->adm ? 1 : 0,
                'reply_url'  => '/guestbook/entry/' . $answer->id,
                'message'    => $this->excerpt->of($answer->text, self::EXCERPT_LENGTH),
            ],
        );
    }

    /**
     * The answers are gone: so are the notifications about them.
     *
     * @param list<int> $entryIds
     */
    public function withdrawn(array $entryIds): void
    {
        if ($entryIds === []) {
            return;
        }

        $this->notifications->deleteForEntities(self::MODULE, self::EVENT, $entryIds);
    }
}
