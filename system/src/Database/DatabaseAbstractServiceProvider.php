<?php

declare(strict_types=1);

namespace Johncms\Database;

use Illuminate\Database\Capsule\Manager;
use Illuminate\Support\Facades\DB;
use Johncms\AbstractServiceProvider;
use PDO;

class DatabaseAbstractServiceProvider extends AbstractServiceProvider
{
    public function register(): void
    {
        $this->container->get(PDO::class);
        $connection = Manager::connection();
        DB::swap($connection);
    }
}
