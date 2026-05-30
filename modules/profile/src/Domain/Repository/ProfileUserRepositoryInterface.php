<?php

declare(strict_types=1);

namespace Johncms\Modules\Profile\Domain\Repository;

use Johncms\Users\User;

interface ProfileUserRepositoryInterface
{
    public function findById(int $id): ?User;
}
