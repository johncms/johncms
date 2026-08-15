<?php

declare(strict_types=1);

namespace Johncms\Modules\Profile\Application\Access;

use Johncms\Auth\Authorization\AccessCheckerInterface;
use Johncms\Modules\Profile\Application\Services\ProfilePermissions;

/**
 * Which kind of ban the visitor may apply.
 *
 * The kinds are stored as numbers in cms_ban_users, and the number used to be matched against
 * the number in users.rights — a forum moderator was allowed the forum ban because 3 was 3. Each
 * kind now names the permission it needs, and the one place that knows the mapping is here:
 * the ban form, the guard of the form and the profile buttons all ask this.
 */
final readonly class BanAccess
{
    /** Ban kinds as they are stored, and the permission each of them needs. */
    private const PERMISSION_BY_TYPE = [
        1  => ProfilePermissions::BAN_MANAGE,
        3  => ProfilePermissions::BAN_MANAGE,
        10 => ProfilePermissions::BAN_MANAGE,
        13 => ProfilePermissions::BAN_MANAGE,
        11 => ProfilePermissions::BAN_FORUM,
        15 => ProfilePermissions::BAN_LIBRARY,
    ];

    public function __construct(private AccessCheckerInterface $accessChecker)
    {
    }

    /**
     * Whether there is any kind of ban this visitor may apply at all. What the ban form and the
     * button leading to it ask, before the hierarchy decides whether this particular account may
     * be banned by them.
     */
    public function mayBanAnything(): bool
    {
        foreach (array_unique(array_values(self::PERMISSION_BY_TYPE)) as $permission) {
            if ($this->accessChecker->allows($permission)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Whether this kind of ban may be applied. A kind nobody declared is refused rather than
     * allowed: the form offers a fixed set, and anything else arrived by hand.
     */
    public function mayApply(int $banType): bool
    {
        $permission = self::PERMISSION_BY_TYPE[$banType] ?? null;

        return $permission !== null && $this->accessChecker->allows($permission);
    }
}
