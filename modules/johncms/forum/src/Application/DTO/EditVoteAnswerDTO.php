<?php

declare(strict_types=1);

namespace Johncms\Modules\Forum\Application\DTO;

final readonly class EditVoteAnswerDTO
{
    public function __construct(
        public int $id,
        public string $name,
    ) {
    }
}
