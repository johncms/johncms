<?php

declare(strict_types=1);

namespace Johncms\Modules\Forum\Application\DTO;

final readonly class PostMessageResultDTO
{
    public function __construct(
        public int $messageId,
        public int $topicId,
        public int $page,
    ) {
    }
}
