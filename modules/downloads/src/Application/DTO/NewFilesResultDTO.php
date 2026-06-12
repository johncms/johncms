<?php

declare(strict_types=1);

namespace Johncms\Modules\Downloads\Application\DTO;

use Illuminate\Support\Collection;

final readonly class NewFilesResultDTO
{
    public function __construct(
        public Collection $files,
        public int $categoryId,
    ) {
    }
}
