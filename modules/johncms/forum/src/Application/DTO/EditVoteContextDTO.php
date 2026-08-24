<?php

declare(strict_types=1);

namespace Johncms\Modules\Forum\Application\DTO;

final readonly class EditVoteContextDTO
{
    /**
     * @param EditVoteAnswerDTO[] $answers
     */
    public function __construct(
        public int $topicId,
        public string $pollName,
        public array $answers,
        public int $savedVote,
    ) {
    }
}
