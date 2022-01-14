<?php

declare(strict_types=1);

namespace Johncms\Security;

use Johncms\Users\User;

class AntiFlood
{
    public function __construct(protected ?User $user = null)
    {
    }

    public function check()
    {
        $config = config('johncms.antiflood');
        switch ($config['mode']) {
            // Adaptive mode
            case 1:
                $admins = User::query()->whereHas('roles')->online()->count();
                $limit = $admins > 0 ? $config['day'] : $config['night'];
                break;
            // Day
            case 3:
                $limit = $config['day'];
                break;
            // Night
            case 4:
                $limit = $config['night'];
                break;
            // Default day/night
            default:
                $currentTime = date('G', time());
                $limit = $currentTime >= $config['day_from'] && $currentTime <= $config['day_to'] ? $config['day'] : $config['night'];
        }

        if ($this->user?->hasAnyRole()) {
            $limit = 2;
        }

        $flood = ($this->user?->activity?->last_post?->timestamp ?? 0) + ($limit - time());
        return max($flood, 0);
    }
}
