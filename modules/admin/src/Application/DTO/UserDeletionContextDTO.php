<?php

declare(strict_types=1);

namespace Johncms\Modules\Admin\Application\DTO;

use Johncms\Users\User;

final readonly class UserDeletionContextDTO
{
    public function __construct(
        public User $user,
        public int $commentsCount,
        public int $forumTopicsCount,
        public int $forumPostsCount,
    ) {
    }
}
