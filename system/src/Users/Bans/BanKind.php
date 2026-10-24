<?php

/**
 * This file is part of JohnCMS Content Management System.
 *
 * @copyright JohnCMS Community
 * @license   https://opensource.org/licenses/GPL-3.0 GPL-3.0
 * @link      https://johncms.com JohnCMS Project
 */

declare(strict_types=1);

namespace Johncms\Users\Bans;

/**
 * What a ban takes away, as cms_ban_users.ban_type stores it. What each kind forbids is decided
 * by BanVoter; this names them.
 */
enum BanKind: int
{
    case SITE = 1;
    case MAIL = 3;
    case COMMENTS = 10;
    case FORUM = 11;
    case GUESTBOOK = 13;
    case LIBRARY = 15;

    public function title(): string
    {
        return match ($this) {
            self::SITE      => d__('system', 'Full block'),
            self::MAIL      => d__('system', 'Private messages'),
            self::COMMENTS  => d__('system', 'Comments'),
            self::FORUM     => d__('system', 'Forum'),
            self::GUESTBOOK => d__('system', 'Guestbook'),
            self::LIBRARY   => d__('system', 'Library'),
        };
    }
}
