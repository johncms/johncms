<?php

declare(strict_types=1);

namespace Johncms\Modules\Mail\Application\DTO;

final readonly class FileItemDTO
{
    public function __construct(
        public int $messageId,
        public int $userId,
        public string $name,
        public bool $userIsOnline,
        public string $fileName,
        public string $fileSize,
        public int $downloadCount,
        public string $downloadUrl,
        public string $profileUrl,
    ) {
    }

    public function toArray(): array
    {
        return [
            'message_id' => $this->messageId,
            'user_id' => $this->userId,
            'name' => $this->name,
            'user_is_online' => $this->userIsOnline,
            'file_name' => $this->fileName,
            'file_size' => $this->fileSize,
            'download_count' => $this->downloadCount,
            'download_url' => $this->downloadUrl,
            'profile_url' => $this->profileUrl,
        ];
    }
}
