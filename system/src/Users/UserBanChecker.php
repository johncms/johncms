<?php

/**
 * This file is part of JohnCMS Content Management System.
 *
 * @copyright JohnCMS Community
 * @license   https://opensource.org/licenses/GPL-3.0 GPL-3.0
 * @link      https://johncms.com JohnCMS Project
 */

declare(strict_types=1);

namespace Johncms\Users;

class UserBanChecker
{
    private ?array $userBans = null;

    public function __construct(private User $user)
    {
    }

    public function getUserBans(): ?array
    {
        if ($this->userBans !== null) {
            return $this->userBans;
        }
        $bans = UserBan::query()->where('user_id', $this->user->id)->active()->get()->toArray();
        $this->userBans = $bans;
        return $this->userBans;
    }

    public function hasBan(array | string $bans): bool
    {
        $userBans = $this->getUserBans();
        $banTypes = array_column($userBans, 'type');

        if (! is_array($bans)) {
            $bans = [$bans];
        }

        $intersectedBans = array_intersect($bans, $banTypes);

        return ! empty($intersectedBans);
    }
}
