<?php

declare(strict_types=1);

namespace Johncms\Modules\Downloads\Application\DTO;

use Illuminate\Support\Collection;

final readonly class SearchFilesResultDTO
{
    public function __construct(
        public Collection $files,
        public string $searchQuery,
        public bool $searchInDescription,
    ) {
    }
}
