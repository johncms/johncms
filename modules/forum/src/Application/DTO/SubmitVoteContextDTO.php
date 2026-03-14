<?php

declare(strict_types=1);

namespace Johncms\Modules\Forum\Application\DTO;

final readonly class SubmitVoteContextDTO
{
    public function __construct(
        public int $topicId,
        public int $voteId,
    ) {
    }
}
