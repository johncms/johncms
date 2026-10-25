<?php

declare(strict_types=1);

namespace Johncms\Modules\Admin\Domain\Enums;

/**
 * Which accounts the ban panel lists: those under a ban now, or everybody who has ever had one.
 */
enum BanListStatus: string
{
    case ACTIVE = 'active';
    case ALL = 'all';
}
