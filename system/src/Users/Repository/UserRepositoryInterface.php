<?php

declare(strict_types=1);

namespace Johncms\Users\Repository;

use Johncms\Users\User;

interface UserRepositoryInterface
{
    /**
     * Register a new guestbook post for the user: increment the postguest
     * counter and refresh the last post timestamp.
     */
    public function registerGuestbookPost(User $user): void;
}
