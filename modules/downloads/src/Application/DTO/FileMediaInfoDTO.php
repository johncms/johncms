<?php

declare(strict_types=1);

namespace Johncms\Modules\Downloads\Application\DTO;

final readonly class FileMediaInfoDTO
{
    public function __construct(
        public string $fileType,
        public array $fileProperties,
        public array $screenshots,
        public ?array $imageInfo,
    ) {
    }
}
