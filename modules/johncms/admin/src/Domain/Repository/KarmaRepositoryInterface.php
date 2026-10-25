<?php

declare(strict_types=1);

namespace Johncms\Modules\Admin\Domain\Repository;

interface KarmaRepositoryInterface
{
    /**
     * The votes of the whole site, the penalties of the bans among them.
     */
    public function countVotes(): int;

    /**
     * Полностью очищает карму: история голосов и счётчики у пользователей.
     */
    public function resetAll(): void;
}
