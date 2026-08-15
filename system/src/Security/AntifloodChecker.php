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

use Illuminate\Database\Eloquent\Builder;
use Johncms\Auth\Authorization\AccessCheckerInterface;
use Johncms\Auth\Authorization\CorePermissions;
use Johncms\Auth\Authorization\UserRole;
use Johncms\Users\User;

final readonly class AntifloodChecker implements AntifloodCheckerInterface
{
    /** The staff are limited by a fixed delay instead of the configured one. */
    private const STAFF_LIMIT = 4;

    public function __construct(
        private User $currentUser,
        private AccessCheckerInterface $accessChecker,
    ) {
    }

    public function getRemainingSeconds(): int
    {
        $limit = $this->accessChecker->allows(CorePermissions::ANTIFLOOD_RELAXED)
            ? self::STAFF_LIMIT
            : $this->getLimit();
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

    /**
     * The staff are the accounts holding a role that was granted to them: the default role
     * everybody signed in has needs no row, and is not what "an administrator is around" means.
     */
    private function hasRecentlyActiveAdmins(): bool
    {
        $now = time();

        return User::query()
            ->where('lastdate', '>', $now - 300)
            ->whereIn(
                'id',
                UserRole::query()
                    ->select('user_id')
                    ->where(
                        static function (Builder $query) use ($now): void {
                            $query->whereNull('expires_at')->orWhere('expires_at', '>', $now);
                        }
                    )
            )
            ->exists();
    }
}
