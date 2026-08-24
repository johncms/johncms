<?php

declare(strict_types=1);

namespace Johncms\Modules\Forum\Application\DTO;

final readonly class NewTopicResultDTO
{
    public function __construct(
        public int $topicId,
        public int $messageId,
        public string $topicUrl,
    ) {
    }
}
