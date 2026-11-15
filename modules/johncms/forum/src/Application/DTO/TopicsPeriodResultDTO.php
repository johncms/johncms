<?php

declare(strict_types=1);

namespace Johncms\Modules\Forum\Application\DTO;

final readonly class TopicsPeriodResultDTO
{
    /**
     * @param list<ForumTopicRowDTO> $topics
     */
    public function __construct(
        public array $topics,
        public int $total,
        public int $hours,
    ) {
    }
}
