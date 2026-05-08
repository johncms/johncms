<?php

declare(strict_types=1);

namespace Johncms\Modules\Downloads\Application\DTO;

use Illuminate\Database\Eloquent\Collection;
use Johncms\Modules\Downloads\Domain\Enums\DownloadTopSort;

final readonly class TopFilesResultDTO
{
    public function __construct(
        public Collection $files,
        public DownloadTopSort $sort,
        public array $buttons,
    ) {
    }
}
