<?php

declare(strict_types=1);

namespace Johncms\Modules\Forum\Application\DTO;

final readonly class BulkDeletePostsContextDTO
{
    public function __construct(
        public int $topicId,
        public string $backUrl,
    ) {
    }
}
