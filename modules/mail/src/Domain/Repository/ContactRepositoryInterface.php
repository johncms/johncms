<?php

declare(strict_types=1);

namespace Johncms\Modules\Mail\Domain\Repository;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Johncms\Modules\Mail\Domain\Models\Contact;

interface ContactRepositoryInterface
{
    /**
     * Get all contacts for a user.
     *
     * @param int $userId User ID
     * @return Collection|Contact[]
     */
    public function getContacts(int $userId): Collection;

    /**
     * Get paginated contacts list for a user (excluding blocked).
     *
     * @param int $userId User ID
     * @param int $perPage Items per page
     * @return LengthAwarePaginator
     */
    public function paginateContacts(int $userId, int $perPage): LengthAwarePaginator;

    /**
     * Count total contacts for a user (excluding blocked).
     *
     * @param int $userId User ID
     * @return int
     */
    public function countContacts(int $userId): int;

    /**
     * Count blocked users for a user.
     *
     * @param int $userId User ID
     * @return int
     */
    public function countBlocked(int $userId): int;

    /**
     * Find a specific contact.
     *
     * @param int $userId Owner ID
     * @param int $contactId Contact user ID
     * @return Contact|null
     */
    public function findContact(int $userId, int $contactId): ?Contact;

    /**
     * Add a user to contacts.
     *
     * @param int $userId Owner ID
     * @param int $contactId Contact user ID
     * @param array $data Additional data (type, friends, etc.)
     * @return Contact
     */
    public function addContact(int $userId, int $contactId, array $data = []): Contact;

    /**
     * Remove a user from contacts.
     *
     * @param int $userId Owner ID
     * @param int $contactId Contact user ID
     */
    public function removeContact(int $userId, int $contactId): void;

    /**
     * Update the last-activity time of a contact record.
     *
     * @param int $userId Owner ID
     * @param int $contactId Contact user ID
     * @param int $time Unix timestamp
     */
    public function updateContactTime(int $userId, int $contactId, int $time): void;

    /**
     * Block a user (add to blacklist).
     *
     * @param int $userId Owner ID
     * @param int $blockedUserId User ID to block
     * @return Contact
     */
    public function blockUser(int $userId, int $blockedUserId): Contact;

    /**
     * Unblock a user (remove from blacklist).
     *
     * @param int $userId Owner ID
     * @param int $blockedUserId User ID to unblock
     */
    public function unblockUser(int $userId, int $blockedUserId): void;

    /**
     * Get blocked users list.
     *
     * @param int $userId Owner ID
     * @return Collection|Contact[]
     */
    public function getBlocklist(int $userId): Collection;

    /**
     * Check if a user is blocked.
     *
     * @param int $userId Owner ID
     * @param int $contactId Contact user ID
     * @return bool
     */
    public function isBlocked(int $userId, int $contactId): bool;
}
