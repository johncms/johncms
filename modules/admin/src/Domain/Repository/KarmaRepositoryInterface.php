<?php

declare(strict_types=1);

namespace Johncms\Modules\Admin\Domain\Repository;

interface KarmaRepositoryInterface
{
    /**
     * Полностью очищает карму: история голосов и счётчики у пользователей.
     */
    public function resetAll(): void;
}
