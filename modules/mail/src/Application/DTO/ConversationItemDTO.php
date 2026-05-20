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

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'count_message' => $this->countMessage,
            'display_date' => $this->displayDate,
            'preview_text' => $this->previewText,
            'unread' => $this->unread,
            'write_url' => $this->writeUrl,
            'buttons' => $this->buttons,
            'user_is_online' => $this->userIsOnline,
            'user_rights_name' => $this->userRightsName,
            'status' => $this->status,
        ];
    }
}
