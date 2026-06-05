<?php

declare(strict_types=1);

namespace Johncms\Modules\Album\Application\DTO;

use Illuminate\Database\Eloquent\Collection;
use Johncms\Modules\Album\Domain\Models\Album;
use Johncms\Users\User;

final readonly class UserAlbumsResultDTO
{
    /**
     * @param Collection<int, Album> $albums
     */
    public function __construct(
        public User $owner,
        public Collection $albums,
        public bool $canCreate,
        public bool $canManage,
    ) {
    }
}
