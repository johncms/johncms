<?php

declare(strict_types=1);

namespace Johncms\Modules\Forum\Domain\Repository;

interface ForumSearchRepositoryInterface
{
    public function countTopicsByName(string $search, bool $includeDeleted): int;

    /**
     * @return array<int, array<string, mixed>>
     */
    public function getTopicsByName(string $search, bool $includeDeleted, int $start, int $limit): array;

    public function countMessagesByText(string $search, bool $includeDeleted): int;

    /**
     * @return array<int, array<string, mixed>>
     */
    public function getMessagesByText(string $search, bool $includeDeleted, int $start, int $limit): array;
}
