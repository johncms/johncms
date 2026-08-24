<?php

declare(strict_types=1);

namespace Johncms\Modules\Mail\Domain\Repository;

use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Collection;
use Johncms\Modules\Mail\Domain\Models\MailMessage;
use Johncms\Users\User;

interface MailMessageRepositoryInterface
{
    public function findById(int $id): ?MailMessage;

    /**
     * Get the last message authored by $authorId addressed to $recipientId.
     */
    public function getLastMessageBetween(int $authorId, int $recipientId): ?MailMessage;

    /**
     * Count the messages in the conversation between two users.
     */
    public function countConversation(int $userId, int $contactId): int;

    /**
     * Get a page of the conversation between two users, newest first.
     *
     * @return EloquentCollection<int, MailMessage>
     */
    public function getConversation(int $userId, int $contactId, int $limit, int $offset): EloquentCollection;

    /**
     * Get a page of the user's attached files, newest first.
     *
     * @return EloquentCollection<int, MailMessage>
     */
    public function getAttachedFiles(int $userId, int $limit, int $offset): EloquentCollection;

    public function save(MailMessage $message): void;

    public function delete(int $id): void;

    /**
     * Mark message as read.
     */
    public function markAsRead(int $id): void;

    /**
     * Mark multiple messages as read by their IDs.
     *
     * @param int[] $ids
     */
    public function markAsReadByIds(array $ids): void;

    public function incrementDownloadCount(int $id): void;

    /**
     * Count total messages between two users (excluding system/spam/deleted).
     *
     * @param int $userId First user ID
     * @param int $contactId Second user ID
     * @return int
     */
    public function countMessagesBetween(int $userId, int $contactId): int;

    /**
     * Count new (unread) messages from contact to user.
     *
     * @param int $userId Recipient user ID
     * @param int $contactId Sender user ID
     * @return int
     */
    public function countNewMessagesFrom(int $userId, int $contactId): int;

    /**
     * Get all messages between two users (excluding system/spam).
     *
     * @param int $userId First user ID
     * @param int $contactId Second user ID
     * @return \Illuminate\Database\Eloquent\Collection|\Johncms\Modules\Mail\Domain\Models\MailMessage[]
     */
    public function getMessagesBetween(int $userId, int $contactId): \Illuminate\Database\Eloquent\Collection;

    /**
     * Count incoming messages for a user (excluding system/spam/deleted and banned contacts).
     */
    public function countInbox(int $userId): int;

    /**
     * Count new (unread) incoming messages for a user.
     */
    public function countNewInbox(int $userId): int;

    /**
     * Count outgoing messages for a user (excluding system/deleted and banned contacts).
     */
    public function countOutbox(int $userId): int;

    /**
     * Count new (unread) outgoing messages for a user.
     */
    public function countNewOutbox(int $userId): int;

    /**
     * Count messages with attachments belonging to a user.
     */
    public function countAttachedFiles(int $userId): int;

    /**
     * Count the distinct senders the user has incoming conversations with.
     */
    public function countIncomingConversations(int $userId): int;

    /**
     * Get a page of incoming conversation partners (User models carrying a last_time attribute),
     * ordered by the last message time descending.
     *
     * @return Collection<int, User>
     */
    public function getIncomingConversations(int $userId, int $limit, int $offset): Collection;

    /**
     * Count the distinct recipients the user has outgoing conversations with.
     */
    public function countOutgoingConversations(int $userId): int;

    /**
     * Get a page of outgoing conversation partners (User models carrying a last_time attribute),
     * ordered by the last message time descending.
     *
     * @return Collection<int, User>
     */
    public function getOutgoingConversations(int $userId, int $limit, int $offset): Collection;
}
