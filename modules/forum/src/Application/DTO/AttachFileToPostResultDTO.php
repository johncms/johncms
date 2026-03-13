<?php

declare(strict_types=1);

namespace Johncms\Modules\Forum\Application\DTO;

final readonly class AttachFileToPostResultDTO
{
    public function __construct(
        public bool $fileAttached,
        public int $topicId,
        public int $page,
    ) {
    }
}
