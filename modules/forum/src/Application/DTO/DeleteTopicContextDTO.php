<?php

declare(strict_types=1);

namespace Johncms\Modules\Forum\Application\DTO;

final readonly class DeleteTopicContextDTO
{
    public function __construct(
        public int $topicId,
        public int $sectionId,
    ) {
    }
}
