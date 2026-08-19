<?php

declare(strict_types=1);

namespace Johncms\View\Twig\Runtime;

use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Database\Eloquent\Builder;
use Johncms\Auth\Authorization\UserRole;
use Johncms\Database\Migrations\PendingMigrations;
use Johncms\Users\Ban;
use Johncms\Users\User;
use Twig\Extension\RuntimeExtensionInterface;

/**
 * The counters of the admin sidebar — users waiting for approval, registered users, staff and
 * active bans — and the notice that the database is behind the code.
 *
 * They used to be four queries run by AdminControllerContext on every request to the admin panel,
 * whether the page drew a sidebar or not. Behind a runtime they are paid for only by the pages
 * that print them.
 */
final class AdminRuntime implements RuntimeExtensionInterface
{
    /** @var array<string, int>|null */
    private ?array $counters = null;

    public function __construct(private readonly PendingMigrations $pendingMigrations)
    {
    }

    /**
     * @return array<string, int>
     */
    public function counters(): array
    {
        return $this->counters ??= [
            'registrations' => User::query()->where('preg', 0)->count(),
            'users'         => User::query()->where('preg', 1)->count(),
            'staff'         => $this->countStaff(),
            'bans'          => Ban::query()->where('ban_time', '>', time())->count(),
        ];
    }

    /**
     * How many migrations the database has not been through yet. Anything above zero means the
     * files on disk expect tables this database does not have.
     */
    public function pendingMigrations(): int
    {
        return $this->pendingMigrations->count();
    }

    /**
     * Accounts holding a role that was granted to them. The default role is not one of those:
     * everybody signed in holds it, without a row and without being staff.
     */
    private function countStaff(): int
    {
        $now = time();

        return UserRole::query()
            ->where(
                static function (Builder $query) use ($now): void {
                    $query->whereNull('expires_at')->orWhere('expires_at', '>', $now);
                }
            )
            ->distinct()
            ->count('user_id');
    }
}
