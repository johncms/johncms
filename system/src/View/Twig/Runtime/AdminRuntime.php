<?php

declare(strict_types=1);

namespace Johncms\View\Twig\Runtime;

use Illuminate\Database\Capsule\Manager as Capsule;
use Johncms\Users\Ban;
use Johncms\Users\User;
use Twig\Extension\RuntimeExtensionInterface;

/**
 * The counters of the admin sidebar: users waiting for approval, registered users, staff and
 * active bans.
 *
 * They used to be four queries run by AdminControllerContext on every request to the admin panel,
 * whether the page drew a sidebar or not. Behind a runtime they are paid for only by the pages
 * that print them.
 */
final class AdminRuntime implements RuntimeExtensionInterface
{
    /** @var array<string, int>|null */
    private ?array $counters = null;

    /**
     * @return array<string, int>
     */
    public function counters(): array
    {
        return $this->counters ??= [
            'registrations' => User::query()->where('preg', 0)->count(),
            'users'         => User::query()->where('preg', 1)->count(),
            'staff'         => User::query()->where('rights', '>=', 1)->count(),
            'bans'          => Ban::query()->where('ban_time', '>', time())->count(),
        ];
    }
}
