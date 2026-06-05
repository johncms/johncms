<?php

declare(strict_types=1);

namespace Johncms\Modules\Profile\Domain\Repository;

use Johncms\Users\User;

interface ProfileUserRepositoryInterface
{
    public function findById(int $id): ?User;

    /**
     * Find a user by the latinized login (name_lat).
     */
    public function findByNameLat(string $nameLat): ?User;

    /**
     * Store a password recovery request: the confirmation code and the request time.
     */
    public function startPasswordRecovery(int $id, string $code, int $time): void;

    /**
     * Clear a password recovery request (both the code and the time).
     */
    public function clearPasswordRecovery(int $id): void;

    /**
     * Apply a recovered password (already hashed) and clear the recovery code.
     */
    public function completePasswordRecovery(int $id, string $hashedPassword): void;

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

    /**
     * Increase the user's positive (karma_plus) or negative (karma_minus) karma counter.
     */
    public function addKarmaPoints(int $id, bool $positive, int $points): void;

    /**
     * Decrease the user's positive/negative karma counter, never going below zero.
     */
    public function subtractKarmaPoints(int $id, bool $positive, int $points): void;

    /**
     * Reset both karma counters (karma_plus/karma_minus) to zero.
     */
    public function resetKarmaTotals(int $id): void;
}
