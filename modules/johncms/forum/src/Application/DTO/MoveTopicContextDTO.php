<?php

declare(strict_types=1);

namespace Johncms\Modules\Forum\Application\DTO;

use Johncms\Modules\Forum\Domain\Models\ForumTopic;

final readonly class MoveTopicContextDTO
{
    /**
     * @param list<array{id: int, title: string}> $targets The sections the topic can go to.
     */
    public function __construct(
        public ForumTopic $topic,
        public array $targets,
    ) {
    }
}
