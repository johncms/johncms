<?php

declare(strict_types=1);

namespace Johncms\Modules\Online\Application\DTO;

final readonly class OnlineItemDTO
{
    public function __construct(
        public int $id,
        public string $name,
        public bool $isOnline,
        public string $profileUrl,
        public string $displayDate,
        public string $placeName,
        public string $ip,
        public string $searchIpUrl,
        public string $ipViaProxy,
        public string $searchIpViaProxyUrl,
        public string $browser,
    ) {
    }
}
