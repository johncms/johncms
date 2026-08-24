<?php

declare(strict_types=1);

namespace Johncms\Modules\Forum\Application\DTO;

final readonly class PostAuthorDTO
{
    public function __construct(
        public ?int $id,
        public string $name,
        public string $avatarUrl,
        public bool $isOnline,
        public ?string $status,
        public ?string $profileUrl,
        public ?string $rightsName,
    ) {
    }
}
