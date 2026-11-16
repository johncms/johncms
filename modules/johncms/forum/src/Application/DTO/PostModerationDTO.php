<?php

declare(strict_types=1);

namespace Johncms\Modules\Forum\Application\DTO;

/**
 * Where a post came from, for those who may see it.
 */
final readonly class PostModerationDTO
{
    public function __construct(
        public string $ip,
        public string $ipViaProxy,
        public string $userAgent,
    ) {
    }
}
