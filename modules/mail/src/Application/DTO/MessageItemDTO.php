<?php

declare(strict_types=1);

namespace Johncms\Modules\Mail\Application\DTO;

final readonly class MessageItemDTO
{
    /**
     * @param array<int, array<string, mixed>> $files
     */
    public function __construct(
        public int $messageId,
        public int $userId,
        public string $name,
        public bool $read,
        public string $text,
        public string $displayDate,
        public bool $userIsOnline,
        public string $userProfileLink,
        public string $userRightsName,
        public string $ip,
        public string $searchIpUrl,
        public string $ipViaProxy,
        public string $searchIpViaProxyUrl,
        public string $browser,
        public string $deleteUrl,
        public array $files,
    ) {
    }

    public function toArray(): array
    {
        return [
            'mid' => $this->messageId,
            'user_id' => $this->userId,
            'name' => $this->name,
            'read' => $this->read,
            'text' => $this->text,
            'display_date' => $this->displayDate,
            'user_is_online' => $this->userIsOnline,
            'user_profile_link' => $this->userProfileLink,
            'user_rights_name' => $this->userRightsName,
            'status' => '',
            'ip' => $this->ip,
            'search_ip_url' => $this->searchIpUrl,
            'ip_via_proxy' => $this->ipViaProxy,
            'search_ip_via_proxy_url' => $this->searchIpViaProxyUrl,
            'browser' => $this->browser,
            'delete_url' => $this->deleteUrl,
            'files' => $this->files,
        ];
    }
}
