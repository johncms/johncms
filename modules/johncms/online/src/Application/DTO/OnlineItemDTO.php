<?php

declare(strict_types=1);

namespace Johncms\Modules\Online\Application\DTO;

use Twig\Markup;

final readonly class OnlineItemDTO
{
    public function __construct(
        public int $id,
        public string $name,
        public bool $isOnline,
        public string $profileUrl,
        public string $displayDate,
        // A link to where the visitor is, markup by contract; null when there is nothing to show.
        public ?Markup $placeName,
        public string $ip,
        public string $searchIpUrl,
        public string $ipViaProxy,
        public string $searchIpViaProxyUrl,
        public string $browser,
    ) {
    }
}
