<?php

declare(strict_types=1);

namespace Johncms\Modules\Forum\Application\DTO;

final readonly class PostActionsDTO
{
    public function __construct(
        public ?string $replyUrl,
        public ?string $quoteUrl,
    ) {
    }
}
