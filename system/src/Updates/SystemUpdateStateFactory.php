<?php

declare(strict_types=1);

namespace Johncms\Updates;

use Johncms\Database\Migrations\PendingMigrations;
use Johncms\Modules\ModuleRegistry;

final readonly class SystemUpdateStateFactory
{
    public function __construct(private PendingMigrations $migrations, private ModuleRegistry $modules)
    {
    }

    public function __invoke(): SystemUpdateState
    {
        $path = config('updates.state_file', DATA_PATH . 'system-update.json');
        if (! is_string($path) || $path === '') {
            throw new \InvalidArgumentException('updates.state_file must name a durable completion marker.');
        }
        return new SystemUpdateState($this->migrations, $this->modules, $path);
    }
}
