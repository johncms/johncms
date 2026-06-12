<?php

declare(strict_types=1);

namespace Johncms\Modules\Downloads\Application\DTO;

use Illuminate\Support\Collection;
use Johncms\Users\User;

final readonly class UserFilesResultDTO
{
    public function __construct(
        public User $user,
        public Collection $files,
    ) {
    }
}
