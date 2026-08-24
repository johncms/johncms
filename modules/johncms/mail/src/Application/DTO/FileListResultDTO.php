<?php

declare(strict_types=1);

namespace Johncms\Modules\Mail\Application\DTO;

final readonly class FileListResultDTO
{
    /**
     * @param \Illuminate\Support\Collection<int, FileItemDTO> $items
     */
    public function __construct(
        public \Illuminate\Support\Collection $items,
        public string $backUrl,
    ) {
    }
}
