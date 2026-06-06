<?php

declare(strict_types=1);

namespace Johncms\Modules\Admin\Domain\Repository;

use Illuminate\Support\Collection;
use Johncms\Users\User;

interface StaffRepositoryInterface
{
    /**
     * Пользователи с правами модератора и выше (rights >= 1), отсортированные по имени.
     *
     * @return Collection<int, User>
     */
    public function getStaff(): Collection;
}
