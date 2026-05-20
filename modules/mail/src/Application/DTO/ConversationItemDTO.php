<?php

declare(strict_types=1);

namespace Johncms\Modules\Mail\Application\DTO;

final readonly class ConversationItemDTO
{
    public function __construct(
        public int $id,
        public string $name,
        public int $countMessage,
        public string $displayDate,
        public string $previewText,
        public bool $unread,
        public string $writeUrl,
        public array $buttons,
        public bool $userIsOnline,
        public ?string $userRightsName = null,
        public ?string $status = null,
    ) {
    }
}
