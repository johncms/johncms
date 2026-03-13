<?php

declare(strict_types=1);

namespace Johncms\Modules\Forum\Application\DTO;

final readonly class PostEditInfoDTO
{
    public function __construct(
        public string $editorName,
        public string $editedAt,
        public int $editCount,
    ) {
    }
}
