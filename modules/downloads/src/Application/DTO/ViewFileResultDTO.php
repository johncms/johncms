<?php

declare(strict_types=1);

namespace Johncms\Modules\Downloads\Application\DTO;

use Illuminate\Database\Eloquent\Collection;
use Johncms\Modules\Downloads\Domain\Models\DownloadFile;
use Johncms\Users\User;

final readonly class ViewFileResultDTO
{
    public function __construct(
        public DownloadFile $file,
        public Collection $additionalFiles,
        public ?User $uploadUser,
    ) {
    }
}
