<?php

declare(strict_types=1);

namespace Johncms\Modules\Mail\Application\DTO;

final readonly class DeleteMessageContextDTO
{
    public function __construct(
        public int $messageId,
        public int $otherUserId,
        public string $backUrl,
    ) {
    }
}
