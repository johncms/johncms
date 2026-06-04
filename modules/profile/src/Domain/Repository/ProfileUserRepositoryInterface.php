<?php

declare(strict_types=1);

namespace Johncms\Modules\Profile\Domain\Repository;

use Johncms\Users\User;

interface ProfileUserRepositoryInterface
{
    public function findById(int $id): ?User;

    /**
     * Mark the user's guestbook as read by syncing the seen counter with the current count.
     */
    public function markGuestbookSeen(int $userId, int $commCount): void;

    /**
     * Apply a previously requested email change: move new_email into mail and clear the pending state.
     */
    public function confirmNewEmail(int $id, string $newEmail): void;

    /**
     * Store the already hashed password for the user.
     */
    public function updatePassword(int $id, string $hashedPassword): void;

    /**
     * Reset the user's personal and forum settings back to defaults.
     */
    public function resetSettings(int $id): void;

    /**
     * Update the user's profile fields. Persisted through a loaded model so the attribute casts apply.
     *
     * @param array<string, mixed> $attributes
     */
    public function updateProfile(int $id, array $attributes): void;

    /**
     * Store the user's personal settings (set_user). Persisted through a loaded model so the cast applies.
     *
     * @param array<string, mixed> $settings
     */
    public function saveUserSettings(int $id, array $settings): void;

    /**
     * Store the user's forum settings (set_forum). Persisted through a loaded model so the cast applies.
     *
     * @param array<string, mixed> $settings
     */
    public function saveForumSettings(int $id, array $settings): void;

    /**
     * Store the user's mail settings (set_mail). Persisted through a loaded model so the cast applies.
     *
     * @param array<string, mixed> $settings
     */
    public function saveMailSettings(int $id, array $settings): void;
}
