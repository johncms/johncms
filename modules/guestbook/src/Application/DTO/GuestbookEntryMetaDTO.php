<?php

declare(strict_types=1);

namespace Johncms\Modules\Guestbook\Application\DTO;

/**
 * What the staff sees under an entry. Where the entry came from and what may be done to it are
 * two separate permissions, so either half can be absent.
 */
final readonly class GuestbookEntryMetaDTO
{
    public function __construct(
        public ?string $ip,
        public ?string $searchIpUrl,
        public ?string $userAgent,
        public bool $canManage,
        public ?string $editUrl,
        public ?string $deleteUrl,
        public ?string $replyUrl,
    ) {
    }
}
