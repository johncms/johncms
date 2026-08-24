<?php

declare(strict_types=1);

namespace Johncms\Modules\Admin\Domain\Repository;

use Johncms\Modules\Admin\Domain\Exceptions\ConfigWriteException;

/**
 * Reading and writing auth.local.php — the gitignored half of the auth configuration, which is
 * where the client secrets of the sign-in services belong.
 */
interface AuthConfigRepositoryInterface
{
    /**
     * @return array<string, mixed> The `auth` key as it currently stands, defaults included.
     */
    public function get(): array;

    /**
     * @param array<string, mixed> $auth
     *
     * @throws ConfigWriteException
     */
    public function save(array $auth): void;
}
