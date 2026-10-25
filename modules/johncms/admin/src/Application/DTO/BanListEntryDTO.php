<?php

declare(strict_types=1);

namespace Johncms\Modules\Admin\Application\DTO;

use Johncms\Users\Ban;
use Johncms\Users\User;

/**
 * An account of the ban panel with the ban it is listed for.
 *
 * @param int $violations All the bans of the account, of any kind and any age.
 */
final readonly class BanListEntryDTO
{
    public function __construct(
        public User $user,
        public Ban $ban,
        public int $violations,
    ) {
    }
}
