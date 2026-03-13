<?php

declare(strict_types=1);

namespace Johncms\Modules\Forum\Application\DTO;

final readonly class PostModerationDTO
{
    public function __construct(
        public string $ip,
        public ?string $ipViaProxy,
        public string $searchIpUrl,
        public ?string $searchIpViaProxyUrl,
        public string $userAgent,
    ) {
    }
}
