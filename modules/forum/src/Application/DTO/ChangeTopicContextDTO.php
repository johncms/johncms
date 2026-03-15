<?php

declare(strict_types=1);

namespace Johncms\Modules\Forum\Application\DTO;

use Johncms\Modules\Forum\Domain\Models\ForumTopic;

final readonly class ChangeTopicContextDTO
{
    public function __construct(
        public ForumTopic $topic,
    ) {
    }
}
