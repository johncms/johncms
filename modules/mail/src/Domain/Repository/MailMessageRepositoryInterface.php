<?php

declare(strict_types=1);

namespace Johncms\Modules\Mail\Domain\Repository;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Johncms\Modules\Mail\Domain\Models\MailMessage;

interface MailMessageRepositoryInterface
{
    public function findById(int $id): ?MailMessage;

    /**
     * Get the last message authored by $authorId addressed to $recipientId.
     */
    public function getLastMessageBetween(int $authorId, int $recipientId): ?MailMessage;

    /**
     * Get conversation between two users.
     *
     * @param int $userId Current user ID
     * @param int $contactId Other user ID
     * @param int $perPage Items per page
     * @return LengthAwarePaginator
     */
    public function getConversation(int $userId, int $contactId, int $perPage): LengthAwarePaginator;

    /**
     * Get incoming messages grouped by sender.
     *
     * @param int $userId Recipient user ID
     * @param int $perPage Items per page
     * @return array Array of senders with their last messages
     */
    public function getIncomingGrouped(int $userId, int $perPage): array;

    /**
     * Get outgoing messages grouped by recipient.
     *
     * @param int $userId Sender user ID
     * @param int $perPage Items per page
     * @return array Array of recipients with their last messages
     */
    public function getOutgoingGrouped(int $userId, int $perPage): array;

    /**
     * Get all attached files for a user.
     *
     * @param int $userId User ID
     * @param int $perPage Items per page
     * @return LengthAwarePaginator
     */
    public function getAttachedFiles(int $userId, int $perPage): LengthAwarePaginator;

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
     * Get incoming conversations grouped by sender with last message preview.
     *
     * @param int $userId Recipient user ID
     * @param int $perPage Items per page
     * @param int $page Page number
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator Paginator with conversation items
     */
    public function getIncomingConversations(int $userId, int $perPage, int $page): \Illuminate\Contracts\Pagination\LengthAwarePaginator;

    /**
     * Get outgoing conversations grouped by recipient with last message preview.
     *
     * @param int $userId Sender user ID
     * @param int $perPage Items per page
     * @param int $page Page number
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator Paginator with conversation items
     */
    public function getOutgoingConversations(int $userId, int $perPage, int $page): \Illuminate\Contracts\Pagination\LengthAwarePaginator;
}
