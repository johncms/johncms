<?php

declare(strict_types=1);

namespace Johncms\Modules\Forum\Application\DTO;

final readonly class UnreadTopicsResultDTO
{
    /**
     * @param list<ForumTopicRowDTO> $topics
     */
    public function __construct(
        public array $topics,
        public int $total,
    ) {
    }
}
