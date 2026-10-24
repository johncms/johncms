<?php

declare(strict_types=1);

namespace Johncms\View\Twig\Runtime;

use Illuminate\Database\Capsule\Manager as Capsule;
use Johncms\Database\Migrations\PendingMigrations;
use Johncms\Users\Ban;
use Johncms\Users\User;
use Twig\Extension\RuntimeExtensionInterface;

/**
 * The counters of the admin sidebar — users waiting for approval and active bans — and the notice
 * that the database is behind the code.
 *
 * Behind a runtime the queries are paid for only by the pages that print them, not by every
 * request to the panel.
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
}
