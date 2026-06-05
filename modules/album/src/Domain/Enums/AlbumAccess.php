<?php

declare(strict_types=1);

namespace Johncms\Modules\Album\Domain\Enums;

/**
 * Album access level.
 *
 * Legacy stored 1/2/3/4, but the UI only ever produced 1/2/4 and value 3 was
 * treated as private (closed) in the album view. We normalize to three explicit
 * levels and collapse any unknown/legacy value into Private (fail-closed).
 */
enum AlbumAccess: int
{
    case Private = 1;
    case Password = 2;
    case Public = 4;

    /**
     * Build from a stored value, treating unknown/legacy values (e.g. 3) as Private.
     */
    public static function fromStored(?int $value): self
    {
        return self::tryFrom((int) $value) ?? self::Private;
    }

    /**
     * The album is hidden from listings and counters (visible to owner/admin only).
     */
    public function isPrivate(): bool
    {
        return $this === self::Private;
    }

    /**
     * The album is protected by a password.
     */
    public function requiresPassword(): bool
    {
        return $this === self::Password;
    }

    /**
     * The album is publicly visible (counts as "new", appears in tops).
     */
    public function isPublic(): bool
    {
        return $this === self::Public;
    }
}
