<?php

declare(strict_types=1);

namespace Johncms\Database\Migrations;

/** A diagnostic read: failures propagate instead of being counted as no pending migrations. */
interface PendingMigrationsProviderInterface
{
    /** @return list<MigrationFile> */
    public function pending(?string $source = null): array;
}
