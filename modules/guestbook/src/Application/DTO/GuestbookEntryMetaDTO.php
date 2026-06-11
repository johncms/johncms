<?php

declare(strict_types=1);

namespace Johncms\Modules\Guestbook\Application\DTO;

final readonly class GuestbookEntryMetaDTO
{
    public function __construct(
        public string $ip,
        public string $searchIpUrl,
        public string $userAgent,
        public bool $canManage,
        public ?string $editUrl,
        public ?string $deleteUrl,
        public ?string $replyUrl,
    ) {
    }
}
