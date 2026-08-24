<?php

declare(strict_types=1);

namespace Johncms\Modules\Forum\Application\DTO;

use Johncms\Modules\Forum\Domain\Models\ForumTopic;

final readonly class FilterByAuthorContextDTO
{
    /**
     * @param array<int, array{user_id:int, user_name:string, count:int}> $authors
     */
    public function __construct(
        public ForumTopic $topic,
        public array $authors,
    ) {
    }
}
