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
 * Matches a permission key against the patterns a role or a token was granted.
 *
 * Keys are dot-separated (`forum.topic.delete`). A pattern is either an exact key, `*` for
 * everything, or a prefix ending in `.*` (`forum.*`, `forum.topic.*`) covering every key below
 * that segment. A star matches whole segments only: `forum.*` covers `forum.topic.delete`,
 * while `forum.top*` matches nothing — partial keys would make a typo silently grant more than
 * it names.
 *
 * Static because it is a pure function over two strings and is called from Identity, which is a
 * value object built without a container.
 */
final class PermissionMatcher
{
    public const WILDCARD = '*';

    public static function matches(string $pattern, string $permission): bool
    {
        if ($pattern === self::WILDCARD || $pattern === $permission) {
            return true;
        }

        if (! str_ends_with($pattern, '.' . self::WILDCARD)) {
            return false;
        }

        // 'forum.*' covers everything under 'forum.', but not the bare 'forum' key itself:
        // a section that is both a key and a prefix would make the pattern ambiguous.
        return str_starts_with($permission, substr($pattern, 0, -1));
    }

    /**
     * @param iterable<string> $patterns
     */
    public static function matchesAny(iterable $patterns, string $permission): bool
    {
        foreach ($patterns as $pattern) {
            if (self::matches($pattern, $permission)) {
                return true;
            }
        }

        return false;
    }
}
