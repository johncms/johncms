<?php

declare(strict_types=1);

namespace Johncms\Modules\Profile\Domain\Repository;

/**
 * How far the owner has read their guestbook: the last entry they saw, by id. New entries get
 * their ids in the order they are written, so whatever comes after is unread.
 */
interface GuestbookReadRepositoryInterface
{
    /** Mark everything written into the guestbook of the owner so far as read. */
    public function markRead(int $ownerId): void;

    /** Entries of others written after the last one the owner saw. */
    public function countUnread(int $ownerId): int;
}
