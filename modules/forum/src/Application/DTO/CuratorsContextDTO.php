<?php

declare(strict_types=1);

namespace Johncms\Modules\Forum\Application\DTO;

use Johncms\Modules\Forum\Domain\Models\ForumTopic;

final readonly class CuratorsContextDTO
{
    /**
     * @param array<array{user_id:int, user_name:string}> $candidates
     */
    public function __construct(
        public ForumTopic $topic,
        public array $candidates,
    ) {
    }
}
