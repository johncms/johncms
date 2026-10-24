<?php

declare(strict_types=1);

namespace Johncms\Users\Bans;

use Johncms\Auth\Authorization\AccessCheckerInterface;
use Johncms\Auth\Authorization\CorePermissions;
use Johncms\Auth\Authorization\RoleLevels;
use Johncms\Auth\CurrentUser;

/**
 * What the visitor may do to the bans of an account: which kind they may apply, and whether they
 * may lift one or delete its record.
 *
 * Each kind of ban (BanKind) names the permission it needs, and the one place that knows the
 * mapping is here: the ban forms of the site and of the panel, the guards of their screens, the
 * histories and the buttons all ask this.
 */
final readonly class BanAccess
{
    /** Ban kinds as they are stored, and the permission each of them needs. */
    private const PERMISSION_BY_TYPE = [
        1  => CorePermissions::USERS_BAN_MANAGE,
        3  => CorePermissions::USERS_BAN_MANAGE,
        10 => CorePermissions::USERS_BAN_MANAGE,
        13 => CorePermissions::USERS_BAN_MANAGE,
        11 => CorePermissions::USERS_BAN_FORUM,
        15 => CorePermissions::USERS_BAN_LIBRARY,
    ];

    public function __construct(
        private AccessCheckerInterface $accessChecker,
        private RoleLevels $roleLevels,
        private CurrentUser $currentUser,
    ) {
    }

    /**
     * The kinds of ban this visitor may apply, in the order a form offers them.
     *
     * @return list<BanKind>
     */
    public function applicableKinds(): array
    {
        return array_values(array_filter(BanKind::cases(), fn (BanKind $kind): bool => $this->mayApply($kind->value)));
    }

    /**
     * Whether there is any kind of ban this visitor may apply at all. What the ban form and the
     * button leading to it ask, before the hierarchy decides whether this particular account may
     * be banned by them.
     */
    public function mayBanAnything(): bool
    {
        foreach (array_keys(self::PERMISSION_BY_TYPE) as $banType) {
            if ($this->mayApply($banType)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Whether this kind of ban may be applied. A kind nobody declared is refused rather than
     * allowed: the form offers a fixed set, and anything else arrived by hand. Nor does anybody
     * hand out the ban they are under: a moderator banned from the forum keeps it only through
     * BAN_MANAGE, which no ban of a section takes away.
     */
    public function mayApply(int $banType): bool
    {
        $permission = self::PERMISSION_BY_TYPE[$banType] ?? null;

        return $permission !== null
            && $this->accessChecker->allows($permission)
            && ! isset($this->currentUser->user()->ban[$banType]);
    }

    /**
     * A ban goes downwards only: whoever gives, lifts or deletes one stands above the account it
     * is on, never level with it and never below — so nobody touches a ban of their own either.
     * Holding the permission is not enough on its own, or an administrator would end the ban the
     * supervisor gave another administrator.
     */
    public function outranks(int $targetId): bool
    {
        return $this->roleLevels->highestGrantedTo($targetId)
            < $this->roleLevels->highest($this->currentUser->identity());
    }

    /**
     * Ending a ban of this account before it runs out.
     */
    public function mayLift(int $targetId): bool
    {
        return $this->accessChecker->allows(CorePermissions::USERS_BAN_PERMANENT) && $this->outranks($targetId);
    }

    /**
     * Deleting the record of a ban of this account, which leaves no trace of it in the history.
     */
    public function mayDestroy(int $targetId): bool
    {
        return $this->accessChecker->allows(CorePermissions::USERS_BAN_DESTROY) && $this->outranks($targetId);
    }
}
