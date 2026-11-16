<?php

declare(strict_types=1);

namespace Johncms\Modules\Forum\Application\DTO;

final readonly class PostAuthorDTO
{
    public function __construct(
        /** Null for a post whose author is gone. */
        public ?int $id,
        public string $name,
        public bool $isOnline,
        public string $status,
        public ?string $profileUrl,
        public string $rightsName,
    ) {
    }
}
