<?php

/**
 * This file is part of JohnCMS Content Management System.
 *
 * @copyright JohnCMS Community
 * @license   https://opensource.org/licenses/GPL-3.0 GPL-3.0
 * @link      https://johncms.com JohnCMS Project
 */

declare(strict_types=1);

namespace Johncms\Security;

use Johncms\Users\User;

final readonly class AntifloodChecker implements AntifloodCheckerInterface
{
    /** Administrators are limited by a fixed delay instead of the configured one. */
    private const ADMIN_LIMIT = 4;

    public function __construct(
        private User $currentUser,
    ) {
    }

    public function getRemainingSeconds(): int
    {
        $limit = $this->currentUser->rights > 0 ? self::ADMIN_LIMIT : $this->getLimit();
        $remaining = $this->currentUser->lastpost + $limit - time();

        return max(0, $remaining);
    }

    private function getLimit(): int
    {
        $config = config('johncms.antiflood', []);
        $day = (int) ($config['day'] ?? 0);
        $night = (int) ($config['night'] ?? 0);

        return match ((int) ($config['mode'] ?? 0)) {
            // Adaptive mode: the day limit applies while at least one administrator is online.
            1       => $this->hasRecentlyActiveAdmins() ? $day : $night,
            3       => $day,
            4       => $night,
            default => $this->getLimitByTimeOfDay($day, $night),
        };
    }

    private function getLimitByTimeOfDay(int $day, int $night): int
    {
        $currentHour = (int) date('G');

        return $currentHour > $day && $currentHour < $night ? $day : $night;
    }

    private function hasRecentlyActiveAdmins(): bool
    {
        return User::query()
            ->where('rights', '>', 0)
            ->where('lastdate', '>', time() - 300)
            ->exists();
    }
}
