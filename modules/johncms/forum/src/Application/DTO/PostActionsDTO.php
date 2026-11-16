<?php

declare(strict_types=1);

namespace Johncms\Modules\Forum\Application\DTO;

/**
 * What the visitor may do with a post; null where they may not.
 */
final readonly class PostActionsDTO
{
    public function __construct(
        public ?string $replyUrl = null,
        public ?string $quoteUrl = null,
        public ?string $editUrl = null,
        public ?string $deleteUrl = null,
        public ?string $restoreUrl = null,
    ) {
    }
}
