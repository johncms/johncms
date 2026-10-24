<?php

declare(strict_types=1);

namespace Johncms\Modules\Admin\Application\Access;

use Johncms\Auth\Authorization\AccessCheckerInterface;
use Johncms\Auth\Authorization\CorePermissions;
use Johncms\Auth\Authorization\RoleLevels;
use Johncms\Auth\CurrentUser;
use Johncms\Users\BanKind;
use Johncms\Users\User;

/**
 * What the visitor may change on an account from the panel: its profile, its password, its
 * settings. Each needs its permission and a place in the hierarchy — the roles say who stands
 * where, and nobody reaches up.
 */
final readonly class UserEditAccess
{
    public function __construct(
        private AccessCheckerInterface $accessChecker,
        private RoleLevels $roleLevels,
        private CurrentUser $currentUser,
    ) {
    }

    /**
     * The profile is edited by its owner, or by whoever holds the permission and does not stand
     * below the owner. An editor banned from the whole site changes none.
     */
    public function mayEdit(User $target): bool
    {
        if (isset($this->currentUser->user()->ban[BanKind::SITE->value])) {
            return false;
        }

        return $this->isSelf($target)
            || ($this->accessChecker->allows(CorePermissions::USERS_PROFILE_EDIT) && ! $this->standsBelow($target));
    }

    /**
     * The password of somebody else, for whoever holds the permission and does not stand below
     * them. The owner changes their own in the settings of the account, where the old one is
     * asked for.
     */
    public function mayChangePassword(User $target): bool
    {
        return ! $this->isSelf($target)
            && $this->accessChecker->allows(CorePermissions::USERS_PASSWORD_CHANGE)
            && ! $this->standsBelow($target);
    }

    /**
     * Wiping the settings of an account is for somebody standing above it: a peer's are not what
     * the permission is for.
     */
    public function mayResetSettings(User $target): bool
    {
        return $this->accessChecker->allows(CorePermissions::USERS_SETTINGS_RESET)
            && $this->roleLevels->highestGrantedTo($target->id) < $this->roleLevels->highest($this->currentUser->identity());
    }

    /**
     * The fields only the staff fill in: the nickname, the gender, the karma, the notes.
     */
    public function mayEditStaffFields(): bool
    {
        return $this->accessChecker->allows(CorePermissions::USERS_PROFILE_EDIT);
    }

    public function mayReadNotes(): bool
    {
        return $this->accessChecker->allows(CorePermissions::USERS_NOTES_VIEW);
    }

    private function isSelf(User $target): bool
    {
        return $target->id === $this->currentUser->id();
    }

    private function standsBelow(User $target): bool
    {
        return $this->roleLevels->highestGrantedTo($target->id) > $this->roleLevels->highest($this->currentUser->identity());
    }
}
