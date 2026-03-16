<?php

declare(strict_types=1);

namespace Johncms\Modules\Forum\Domain\Query;

final readonly class ForumFileScopeQuery
{
    public function __construct(
        public ?int $categoryId,
        public ?int $sectionId,
        public ?int $topicId,
        public bool $includeDeleted,
    ) {
    }
}
