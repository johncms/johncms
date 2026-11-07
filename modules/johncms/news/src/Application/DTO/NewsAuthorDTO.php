<?php

declare(strict_types=1);

namespace Johncms\Modules\News\Application\DTO;

final readonly class NewsAuthorDTO
{
    /** @param int $joinedAt When the author registered, a Unix timestamp; 0 when unknown. */
    public function __construct(
        public int $id,
        public string $name,
        public string $status = '',
        public bool $online = false,
        public string $rightsName = '',
        public int $joinedAt = 0,
    ) {
    }
}
