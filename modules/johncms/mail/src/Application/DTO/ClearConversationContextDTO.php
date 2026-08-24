<?php

declare(strict_types=1);

namespace Johncms\Modules\Mail\Application\DTO;

final readonly class ClearConversationContextDTO
{
    public function __construct(
        public int $contactId,
        public string $backUrl,
    ) {
    }
}
