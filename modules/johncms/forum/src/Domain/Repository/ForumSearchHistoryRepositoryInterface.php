<?php

declare(strict_types=1);

namespace Johncms\Modules\Forum\Domain\Repository;

interface ForumSearchHistoryRepositoryInterface
{
    /**
     * @return string[]
     */
    public function getByUserId(int $userId): array;

    /**
     * @param string[] $history
     */
    public function saveForUser(int $userId, array $history): void;

    public function clearForUser(int $userId): void;
}
