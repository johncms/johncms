<?php

declare(strict_types=1);

namespace Johncms\Modules\Forum\Application\Access;

use Johncms\Auth\CurrentUser;

final readonly class ForumAccess
{
    public function __construct(private CurrentUser $currentUser)
    {
    }

    /**
     * Whether the visitor is banned from the forum or from the whole site, and may write nothing in
     * it. BanVoter takes the permissions of the forum away from such an account; this answers for
     * what asks for no permission at all, and for a forum that lets guests write.
     */
    public function isBanned(): bool
    {
        $bans = $this->currentUser->user()->ban;

        return isset($bans[1]) || isset($bans[11]);
    }
}
