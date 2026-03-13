<?php

declare(strict_types=1);

namespace Johncms\Modules\Forum\Application\DTO;

final readonly class PostFileDTO
{
    public function __construct(
        public string $filename,
        public string $fileUrl,
        public ?string $previewUrl,
        public string $fileSize,
        public int $downloadCount,
    ) {
    }
}
