<?php

declare(strict_types=1);

namespace Johncms\Modules\Library\Application\Access;

use Johncms\Auth\CurrentUser;

final readonly class LibraryAccess
{
    public function __construct(private CurrentUser $currentUser)
    {
    }

    /**
     * Whether a member may write in the library: an article, a change of one of their own, a vote.
     * A ban from the library or from the whole site takes all of it away.
     */
    public function mayWrite(): bool
    {
        return $this->currentUser->isValid()
            && ! isset($this->currentUser->user()->ban['1'])
            && ! isset($this->currentUser->user()->ban['15']);
    }
}
