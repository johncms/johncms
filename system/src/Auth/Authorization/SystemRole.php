<?php

/**
 * This file is part of JohnCMS Content Management System.
 *
 * @copyright JohnCMS Community
 * @license   https://opensource.org/licenses/GPL-3.0 GPL-3.0
 * @link      https://johncms.com JohnCMS Project
 */

declare(strict_types=1);

namespace Johncms\Auth\Authorization;

/**
 * The roles every installation has, and what the numeric users.rights they replace mapped to.
 *
 * They cannot be deleted or renamed by slug, because code refers to them; everything else about
 * them — their name, and above all which permissions they carry — is the site's to change.
 *
 * The levels are spaced out on purpose: a site adding a role of its own between two of these
 * should not have to renumber anything.
 */
enum SystemRole: string
{
    case Guest = 'guest';
    case User = 'user';
    case ForumModerator = 'forum-moderator';
    case DownloadsModerator = 'downloads-moderator';
    case LibraryModerator = 'library-moderator';
    case SuperModerator = 'super-moderator';
    case Admin = 'admin';
    case Supervisor = 'supervisor';

    /**
     * Level from which a role may do anything at all. Guarantees a way back into a site whose
     * permissions were misconfigured — see SuperAdminVoter.
     */
    public const SUPERVISOR_LEVEL = 90;

    /**
     * Whether a role standing this high may do anything at all, whatever permissions it carries.
     * The one rule of the hierarchy that is not a permission, so it lives next to the level it
     * compares against rather than in whoever happens to ask.
     */
    public static function grantsEverything(int $level): bool
    {
        return $level >= self::SUPERVISOR_LEVEL;
    }

    public function level(): int
    {
        return match ($this) {
            self::Guest => 0,
            self::User => 10,
            self::ForumModerator, self::DownloadsModerator, self::LibraryModerator => 30,
            self::SuperModerator => 60,
            self::Admin => 70,
            self::Supervisor => self::SUPERVISOR_LEVEL,
        };
    }

    /**
     * The users.rights value this role stands for, or null when it has no numeric equivalent —
     * the guest role never was one.
     */
    public function legacyRights(): ?int
    {
        return match ($this) {
            self::Guest => null,
            self::User => 0,
            self::ForumModerator => 3,
            self::DownloadsModerator => 4,
            self::LibraryModerator => 5,
            self::SuperModerator => 6,
            self::Admin => 7,
            self::Supervisor => 9,
        };
    }

    /**
     * Whether the role applies to every signed-in visitor without a row of its own. Only 'user'
     * does, which is why a site with a hundred thousand accounts stores a handful of rows
     * rather than a hundred thousand.
     */
    public function isDefault(): bool
    {
        return $this === self::User;
    }

    public function isGuest(): bool
    {
        return $this === self::Guest;
    }

    /**
     * The name as it is seeded and shown. Translated by slug rather than stored per language:
     * the column holds one string, and a site renaming a role keeps its own wording.
     */
    public function label(): string
    {
        return match ($this) {
            self::Guest => d__('system', 'Guest'),
            self::User => d__('system', 'User'),
            self::ForumModerator => d__('system', 'Forum moderator'),
            self::DownloadsModerator => d__('system', 'Download moderator'),
            self::LibraryModerator => d__('system', 'Library moderator'),
            self::SuperModerator => d__('system', 'Super moderator'),
            self::Admin => d__('system', 'Administrator'),
            self::Supervisor => d__('system', 'Supervisor'),
        };
    }

    /**
     * The role a numeric users.rights value stands for, or null when the value is not one of the
     * documented ones. The migration decides what to do with those; it must not silently drop
     * them, because checks like `rights >= 1` gave the undocumented values meaning too.
     */
    public static function fromLegacyRights(int $rights): ?self
    {
        foreach (self::cases() as $role) {
            if ($role->legacyRights() === $rights) {
                return $role;
            }
        }

        return null;
    }

    /**
     * The closest role at or below the given numeric value. Used for the values nobody
     * documented (1, 2, 8): mapping down never grants more than the account had.
     */
    public static function nearestBelowLegacyRights(int $rights): self
    {
        $best = self::User;

        foreach (self::cases() as $role) {
            $legacy = $role->legacyRights();

            if ($legacy !== null && $legacy <= $rights && $legacy >= (int) $best->legacyRights()) {
                $best = $role;
            }
        }

        return $best;
    }
}
